<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Entity\ContractSignature;
use App\Service\SignatureService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contrat/signature/{token}', requirements: ['token' => '[a-f0-9]{64}'])]
final class SignatureController extends AbstractController
{
    public function __construct(
        private readonly SignatureService $signatures,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.mail_from%')]
        private readonly string $mailFrom,
    ) {
    }

    /** Page de signature (états : à signer, signé, expiré). */
    #[Route('', name: 'contract_sign', methods: ['GET'])]
    public function show(string $token): Response
    {
        $sig = $this->find($token);
        return $this->render('signature/sign.html.twig', [
            'sig' => $sig,
            'token' => $token,
            'phone' => $this->maskPhone($sig->signerPhone),
        ])->setPrivate()->setMaxAge(0);
    }

    /** PDF du contrat (signé si le contrat est signé). */
    #[Route('/document', name: 'contract_sign_document', methods: ['GET'])]
    public function document(string $token): Response
    {
        $sig = $this->find($token);

        try {
            $content = $this->signatures->readPdf($sig);
        } catch (\RuntimeException) {
            throw $this->createNotFoundException();
        }

        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="contrat-vente-' . $sig->contractNumber . '.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /** Envoie le code à usage unique par SMS. */
    #[Route('/code', name: 'contract_sign_otp', methods: ['POST'])]
    public function sendCode(string $token, Request $request): JsonResponse
    {
        $sig = $this->find($token);
        if (!$this->isCsrfTokenValid('sign-' . $token, (string) $request->request->get('_token'))) {
            return $this->json(['error' => 'Session expirée, rechargez la page.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->signatures->sendOtp($sig);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            $this->logger->error('Envoi du code de signature impossible', ['exception' => $e, 'contract' => $sig->contractNumber]);

            return $this->json(['error' => "Le SMS n'a pas pu être envoyé. Réessayez dans un instant."], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json(['ok' => true]);
    }

    /** Vérifie le code, enregistre la signature et envoie le PDF signé par e-mail. */
    #[Route('/signer', name: 'contract_sign_submit', methods: ['POST'])]
    public function submit(string $token, Request $request): JsonResponse
    {
        $sig = $this->find($token);
        if (!$this->isCsrfTokenValid('sign-' . $token, (string) $request->request->get('_token'))) {
            return $this->json(['error' => 'Session expirée, rechargez la page.'], Response::HTTP_FORBIDDEN);
        }
        if ($request->request->get('consent') !== '1') {
            return $this->json(['error' => 'Vous devez accepter les conditions du contrat.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            if (!$this->signatures->isOtpVerified($sig) && !$this->signatures->verifyOtp($sig, (string) $request->request->get('code'))) {
                return $this->json(['error' => 'Code incorrect.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // getClientIp() n'est fiable derrière un proxy que si framework.trusted_proxies est configuré.
            $pdf = $this->signatures->sign(
                $sig,
                (string) $request->request->get('signature'),
                (string) $request->getClientIp(),
                (string) $request->headers->get('User-Agent'),
            );
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->sendSignedCopy($sig, $pdf);

        return $this->json(['ok' => true]);
    }

    private function sendSignedCopy(ContractSignature $sig, string $pdf): void
    {
        $email = (new Email())
            ->from(new Address($this->mailFrom, 'AutoPro'))
            ->to(new Address($sig->signerEmail, $sig->signerName))
            ->subject('Votre contrat de vente signé n° ' . $sig->contractNumber)
            ->text("Bonjour,\n\nVotre contrat de vente n° " . $sig->contractNumber . " a bien été signé. Vous en trouverez un exemplaire en pièce jointe.\n\nAutoPro")
            ->attach($pdf, 'contrat-vente-' . $sig->contractNumber . '.pdf', 'application/pdf');

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // La signature est enregistrée : un échec d'envoi ne doit pas l'annuler.
            $this->logger->error("Envoi du contrat signé impossible", ['exception' => $e, 'contract' => $sig->contractNumber]);
        }
    }

    private function find(string $token): ContractSignature
    {
        return $this->signatures->findByToken($token) ?? throw $this->createNotFoundException();
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return strlen($digits) >= 4 ? '•• •• •• ' . substr($digits, -2) : '••••';
    }
}
