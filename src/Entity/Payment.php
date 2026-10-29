<?php
// src/Entity/Payment.php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payments')]
class Payment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 64, unique: true)]
    private string $molliePaymentId;

    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(length: 32)]
    private string $status = 'open'; // open, pending, paid, failed, expired, canceled

    #[ORM\Column(length: 255)]
    private string $description;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $orderReference = null; // ex: référence de réservation Luxe Car

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Reservation::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private Reservation $reservation;

    #[ORM\Column(length: 20)]
    private string $method = 'mollie'; // 'mollie' | 'virement_manuel'

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    public function __construct(string $molliePaymentId, string $amount, string $currency, string $description)
    {
        $this->id = Uuid::v4();
        $this->molliePaymentId = $molliePaymentId;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getMolliePaymentId(): string { return $this->molliePaymentId; }
    public function getStatus(): string { return $this->status; }

    public function getReservation(): Reservation { return $this->reservation; }
    public function setReservation(Reservation $reservation): static
    {
        $this->reservation = $reservation;
        return $this;
    }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getMethod(): ?string { return $this->method; }
    public function setMethod(?string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
 
 
    public function setStatus(string $status): static
    {
        $this->status = $status;
        if ($status === 'paid' && $this->paidAt === null) {
            $this->paidAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function setOrderReference(?string $ref): static
    {
        $this->orderReference = $ref;
        return $this;
    }

    public function getOrderReference(): ?string { return $this->orderReference; }

    public function getMetadata(): ?array { return $this->metadata; }
    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getAmount(): string { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
}