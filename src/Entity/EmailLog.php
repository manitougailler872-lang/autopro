<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EmailLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailLogRepository::class)]
#[ORM\Table(name: 'email_logs')]
class EmailLog
{
    public const STATUS_SENT   = 'SENT';
    public const STATUS_FAILED = 'FAILED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Reservation $reservation = null;

    #[ORM\Column(length: 190)]
    private string $recipient = '';

    #[ORM\Column(length: 190)]
    private string $subject = '';

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $providerMessageId = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_SENT;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(?Reservation $v): self { $this->reservation = $v; return $this; }
    public function getRecipient(): string { return $this->recipient; }
    public function setRecipient(string $v): self { $this->recipient = $v; return $this; }
    public function getSubject(): string { return $this->subject; }
    public function setSubject(string $v): self { $this->subject = $v; return $this; }
    public function getProviderMessageId(): ?string { return $this->providerMessageId; }
    public function setProviderMessageId(?string $v): self { $this->providerMessageId = $v; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): self { $this->status = $v; return $this; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(?string $v): self { $this->errorMessage = $v; return $this; }
    public function getSentAt(): ?\DateTimeImmutable { return $this->sentAt; }
    public function setSentAt(?\DateTimeImmutable $v): self { $this->sentAt = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
