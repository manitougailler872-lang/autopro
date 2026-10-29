<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une demande de signature électronique d'un contrat de vente, avec ses preuves.
 * Le jeton du lien n'est jamais stocké en clair : seul son hash SHA-256 l'est.
 */
#[ORM\Entity]
#[ORM\Table(name: 'contract_signature')]
class ContractSignature
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    public string $tokenHash;

    #[ORM\Column(length: 40)]
    public string $contractNumber;

    #[ORM\Column(length: 150)]
    public string $signerName;

    #[ORM\Column(length: 180)]
    public string $signerEmail;

    #[ORM\Column(length: 30)]
    public string $signerPhone;

    #[ORM\Column(length: 16)]
    public string $status = self::STATUS_PENDING;

    /** Données du contrat figées à la création : le PDF signé est rendu à partir d'elles. */
    #[ORM\Column(type: Types::JSON)]
    public array $context = [];

    /** Nom du fichier dans var/contracts/ (jamais dans public/). */
    #[ORM\Column(length: 255)]
    public string $unsignedPdfFile;

    /** Empreinte SHA-256 du PDF soumis à signature. */
    #[ORM\Column(length: 64)]
    public string $unsignedPdfHash;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $signedPdfFile = null;

    #[ORM\Column(length: 64, nullable: true)]
    public ?string $signedPdfHash = null;

    // ---- Code à usage unique (SMS) ----
    #[ORM\Column(length: 64, nullable: true)]
    public ?string $otpHash = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $otpSentAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $otpExpiresAt = null;

    #[ORM\Column(type: Types::SMALLINT)]
    public int $otpAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $otpVerifiedAt = null;

    // ---- Preuves de la signature ----
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $signedAt = null;

    #[ORM\Column(length: 45, nullable: true)]
    public ?string $signerIp = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $signerUserAgent = null;

    #[ORM\Column(length: 20, nullable: true)]
    public ?string $transactionId = null;

    public function __construct(
        string $tokenHash,
        string $contractNumber,
        string $signerName,
        string $signerEmail,
        string $signerPhone,
        array $context,
        string $unsignedPdfFile,
        string $unsignedPdfHash,
        \DateTimeImmutable $expiresAt,
    ) {
        $this->tokenHash = $tokenHash;
        $this->contractNumber = $contractNumber;
        $this->signerName = $signerName;
        $this->signerEmail = $signerEmail;
        $this->signerPhone = $signerPhone;
        $this->context = $context;
        $this->unsignedPdfFile = $unsignedPdfFile;
        $this->unsignedPdfHash = $unsignedPdfHash;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $expiresAt;
    }

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }
}
