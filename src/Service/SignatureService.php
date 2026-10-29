<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContractSignature;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SignatureService
{
    private const LINK_TTL = 'P7D';           // validité du lien : 7 jours
    private const OTP_TTL = 'PT10M';          // validité du code : 10 minutes
    private const OTP_VERIFIED_TTL = 900;     // le code validé reste utilisable 15 minutes
    private const OTP_RESEND_SECONDS = 60;
    private const OTP_MAX_ATTEMPTS = 5;
    private const MAX_SIGNATURE_BYTES = 300_000;
    private const PNG_PREFIX = 'data:image/png;base64,';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ContractPdfGenerator $pdf,
        //private readonly OtpSenderInterface $otp,
        #[Autowire('%kernel.project_dir%/var/contracts')]
        private readonly string $storageDir,
        #[Autowire('%kernel.secret%')]
        private readonly string $secret,
    ) {
    }

    /**
     * Crée la demande de signature : génère et archive le PDF à signer, en calcule l'empreinte.
     *
     * @param array<string, mixed> $context tableaux de scalaires (dates au format 'Y-m-d'), voir contrat_vente.html.twig
     *
     * @return array{0: ContractSignature, 1: string} l'entité et le jeton EN CLAIR (à mettre dans le lien envoyé au client)
     */
    public function createRequest(array $context, string $signerName, string $signerEmail, string $signerPhone): array
    {
        $token = bin2hex(random_bytes(32));
        $number = (string) $context['contract']['number'];

        $bytes = $this->pdf->render($context);
        $file = $this->store($number, 'a-signer', $bytes);

        $signature = new ContractSignature(
            tokenHash: hash('sha256', $token),
            contractNumber: $number,
            signerName: $signerName,
            signerEmail: $signerEmail,
            signerPhone: $signerPhone,
            context: $context,
            unsignedPdfFile: $file,
            unsignedPdfHash: hash('sha256', $bytes),
            expiresAt: (new \DateTimeImmutable())->add(new \DateInterval(self::LINK_TTL)),
        );
        $this->em->persist($signature);
        $this->em->flush();

        return [$signature, $token];
    }

    public function findByToken(string $token): ?ContractSignature
    {
        $signature = $this->em->getRepository(ContractSignature::class)->findOneBy(['tokenHash' => hash('sha256', $token)]);

        if ($signature !== null && $signature->status === ContractSignature::STATUS_PENDING && $signature->expiresAt < new \DateTimeImmutable()) {
            $signature->status = ContractSignature::STATUS_EXPIRED;
            $this->em->flush();
        }

        return $signature;
    }

    /** Envoie un code à 6 chiffres par SMS. */
    public function sendOtp(ContractSignature $signature): void
    {
        $this->assertOpen($signature);

        $now = new \DateTimeImmutable();
        if ($signature->otpSentAt !== null && $now->getTimestamp() - $signature->otpSentAt->getTimestamp() < self::OTP_RESEND_SECONDS) {
            throw new \DomainException('Veuillez patienter une minute avant de demander un nouveau code.');
        }

        $code = (string) random_int(100000, 999999);
        $signature->otpHash = $this->hashOtp($signature, $code);
        $signature->otpSentAt = $now;
        $signature->otpExpiresAt = $now->add(new \DateInterval(self::OTP_TTL));
        $signature->otpAttempts = 0;
        $signature->otpVerifiedAt = null;
        $this->em->flush();

        //$this->otp->send($signature->signerPhone, $code, $signature->contractNumber);
    }

    /** Vérifie le code saisi. Limité à 5 tentatives par code. */
    public function verifyOtp(ContractSignature $signature, string $code): bool
    {
        $this->assertOpen($signature);

        if ($signature->otpHash === null || $signature->otpExpiresAt === null || $signature->otpExpiresAt < new \DateTimeImmutable()) {
            throw new \DomainException('Le code a expiré. Demandez un nouveau code.');
        }
        if ($signature->otpAttempts >= self::OTP_MAX_ATTEMPTS) {
            throw new \DomainException('Trop de tentatives. Demandez un nouveau code.');
        }

        ++$signature->otpAttempts;
        $ok = hash_equals($signature->otpHash, $this->hashOtp($signature, trim($code)));
        if ($ok) {
            $signature->otpVerifiedAt = new \DateTimeImmutable();
            $signature->otpHash = null; // usage unique
        }
        $this->em->flush();

        return $ok;
    }

    public function isOtpVerified(ContractSignature $signature): bool
    {
        return $signature->otpVerifiedAt !== null
            && (new \DateTimeImmutable())->getTimestamp() - $signature->otpVerifiedAt->getTimestamp() <= self::OTP_VERIFIED_TTL;
    }

    /**
     * Enregistre la signature : rend le PDF signé (image + page de preuve), l'archive et scelle son empreinte.
     *
     * @return string le contenu du PDF signé
     */
    public function sign(ContractSignature $signature, string $signatureDataUrl, string $ip, string $userAgent): string
    {
        $this->assertOpen($signature);
        if (!$this->isOtpVerified($signature)) {
            throw new \DomainException("Le code de vérification n'a pas été validé.");
        }

        $png = $this->decodePng($signatureDataUrl);
        $signedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $transactionId = strtoupper(bin2hex(random_bytes(6)));

        $bytes = $this->pdf->render($signature->context, [
            'signerName' => $signature->signerName,
            'signerEmail' => $signature->signerEmail,
            'signerPhone' => $this->maskPhone($signature->signerPhone),
            'image' => self::PNG_PREFIX . base64_encode($png),
            'signedAt' => $this->formatUtc($signedAt),
            'ip' => $ip,
            'userAgent' => mb_substr($userAgent, 0, 160),
            'otpVerifiedAt' => $this->formatUtc($signature->otpVerifiedAt),
            'documentHash' => $signature->unsignedPdfHash,
            'transactionId' => $transactionId,
        ]);

        $signature->signedPdfFile = $this->store($signature->contractNumber, 'signe', $bytes);
        $signature->signedPdfHash = hash('sha256', $bytes);
        $signature->signedAt = $signedAt;
        $signature->signerIp = $ip;
        $signature->signerUserAgent = mb_substr($userAgent, 0, 255);
        $signature->transactionId = $transactionId;
        $signature->status = ContractSignature::STATUS_SIGNED;
        $this->em->flush();

        return $bytes;
    }

    /** Contenu du PDF signé si le contrat est signé, sinon du PDF à signer. */
    public function readPdf(ContractSignature $signature): string
    {
        $file = $signature->isSigned() ? $signature->signedPdfFile : $signature->unsignedPdfFile;
        $content = is_string($file) ? @file_get_contents($this->storageDir . '/' . $file) : false;

        if ($content === false) {
            throw new \RuntimeException('Document introuvable.');
        }

        return $content;
    }

    private function assertOpen(ContractSignature $signature): void
    {
        if ($signature->isSigned()) {
            throw new \DomainException('Ce contrat est déjà signé.');
        }
        if ($signature->status === ContractSignature::STATUS_EXPIRED || $signature->expiresAt < new \DateTimeImmutable()) {
            throw new \DomainException('Ce lien de signature a expiré. Contactez-nous pour en recevoir un nouveau.');
        }
    }

    private function hashOtp(ContractSignature $signature, string $code): string
    {
        return hash_hmac('sha256', $signature->tokenHash . ':' . $code, $this->secret);
    }

    private function decodePng(string $dataUrl): string
    {
        if (!str_starts_with($dataUrl, self::PNG_PREFIX)) {
            throw new \DomainException('Signature invalide.');
        }

        $png = base64_decode(substr($dataUrl, strlen(self::PNG_PREFIX)), true);
        if ($png === false || $png === '' || strlen($png) > self::MAX_SIGNATURE_BYTES) {
            throw new \DomainException('Signature invalide.');
        }

        $info = @getimagesizefromstring($png);
        if ($info === false || $info[2] !== IMAGETYPE_PNG) {
            throw new \DomainException('Signature invalide.');
        }

        return $png;
    }

    /** Date en UTC, lisible : 20/09/2026 à 14:32:05 UTC (pas de "\à" dans format() : caractère multi-octets). */
    private function formatUtc(\DateTimeInterface $date): string
    {
        $utc = \DateTimeImmutable::createFromInterface($date)->setTimezone(new \DateTimeZone('UTC'));

        return $utc->format('d/m/Y') . ' à ' . $utc->format('H:i:s') . ' UTC';
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return strlen($digits) >= 4 ? '•• •• •• ' . substr($digits, -2) : '••••';
    }

    /** Écrit le fichier dans var/contracts/ (hors du dossier public) et retourne son nom. */
    private function store(string $contractNumber, string $suffix, string $bytes): string
    {
        if (!is_dir($this->storageDir) && !mkdir($this->storageDir, 0750, true) && !is_dir($this->storageDir)) {
            throw new \RuntimeException('Dossier de stockage des contrats inaccessible.');
        }

        $name = preg_replace('/[^A-Za-z0-9_-]/', '', $contractNumber) . '-' . $suffix . '-' . bin2hex(random_bytes(4)) . '.pdf';
        if (file_put_contents($this->storageDir . '/' . $name, $bytes) === false) {
            throw new \RuntimeException("Impossible d'écrire le contrat.");
        }

        return $name;
    }
}
