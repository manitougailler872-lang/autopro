<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RefundStatus;
use App\Repository\RefundRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RefundRequestRepository::class)]
#[ORM\Table(name: 'refund_requests')]
#[ORM\Index(name: 'idx_refund_status', columns: ['status'])]
class RefundRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\ManyToOne(targetEntity: Reservation::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reservation $reservation = null;

    #[ORM\Column(length: 80)]
    private string $reason = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerComment = null;

    #[ORM\Column(type: 'text')]
    private string $encryptedIban = '';

    #[ORM\Column(length: 4)]
    private string $ibanLastFour = '';

    #[ORM\Column(length: 20, enumType: RefundStatus::class)]
    private RefundStatus $status = RefundStatus::RECEIVED;

    #[ORM\Column]
    private \DateTimeImmutable $requestedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminNote = null;

    public function __construct()
    {
        $this->uuid = Uuid::v7();
        $this->requestedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUuid(): Uuid { return $this->uuid; }
    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(Reservation $v): self { $this->reservation = $v; return $this; }
    public function getReason(): string { return $this->reason; }
    public function setReason(string $v): self { $this->reason = $v; return $this; }
    public function getCustomerComment(): ?string { return $this->customerComment; }
    public function setCustomerComment(?string $v): self { $this->customerComment = $v; return $this; }
    public function getEncryptedIban(): string { return $this->encryptedIban; }
    public function setEncryptedIban(string $v): self { $this->encryptedIban = $v; return $this; }
    public function getIbanLastFour(): string { return $this->ibanLastFour; }
    public function setIbanLastFour(string $v): self { $this->ibanLastFour = $v; return $this; }
    public function getStatus(): RefundStatus { return $this->status; }
    public function setStatus(RefundStatus $v): self { $this->status = $v; return $this; }
    public function getRequestedAt(): \DateTimeImmutable { return $this->requestedAt; }
    public function getProcessedAt(): ?\DateTimeImmutable { return $this->processedAt; }
    public function setProcessedAt(?\DateTimeImmutable $v): self { $this->processedAt = $v; return $this; }
    public function getAdminNote(): ?string { return $this->adminNote; }
    public function setAdminNote(?string $v): self { $this->adminNote = $v; return $this; }
    public function getMaskedIban(): string { return 'FR76 •••• •••• •••• '.$this->ibanLastFour; }
}
