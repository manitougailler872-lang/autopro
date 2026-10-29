<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PaymentProofStatus;
use App\Repository\PaymentProofRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Reservation;

#[ORM\Entity(repositoryClass: PaymentProofRepository::class)]
#[ORM\Table(name: 'payment_proofs')]
class PaymentProof
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class, inversedBy: 'paymentProofs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Reservation $reservation = null;

    #[ORM\Column(length: 190)]
    private string $originalFilename = '';

    #[ORM\Column(length: 190)]
    private string $storedFilename = '';

    #[ORM\Column(length: 100)]
    private string $mimeType = '';

    #[ORM\Column]
    private int $fileSize = 0;

    #[ORM\Column(length: 20, enumType: PaymentProofStatus::class)]
    private PaymentProofStatus $status = PaymentProofStatus::PENDING;

    #[ORM\Column]
    private \DateTimeImmutable $uploadedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    public function __construct() { $this->uploadedAt = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(?Reservation $v): self { $this->reservation = $v; return $this; }
    public function getOriginalFilename(): string { return $this->originalFilename; }
    public function setOriginalFilename(string $v): self { $this->originalFilename = $v; return $this; }
    public function getStoredFilename(): string { return $this->storedFilename; }
    public function setStoredFilename(string $v): self { $this->storedFilename = $v; return $this; }
    public function getMimeType(): string { return $this->mimeType; }
    public function setMimeType(string $v): self { $this->mimeType = $v; return $this; }
    public function getFileSize(): int { return $this->fileSize; }
    public function setFileSize(int $v): self { $this->fileSize = $v; return $this; }
    public function getStatus(): PaymentProofStatus { return $this->status; }
    public function setStatus(PaymentProofStatus $v): self { $this->status = $v; return $this; }
    public function getUploadedAt(): \DateTimeImmutable { return $this->uploadedAt; }
    public function getReviewedAt(): ?\DateTimeImmutable { return $this->reviewedAt; }
    public function setReviewedAt(?\DateTimeImmutable $v): self { $this->reviewedAt = $v; return $this; }
}
