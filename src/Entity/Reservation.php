<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Enum\ReceptionMode;
use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservations')]
#[ORM\Index(name: 'idx_res_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_res_number', columns: ['reservation_number'])]
#[ORM\Index(name: 'idx_res_email', columns: ['customer_email'])]
#[ORM\Index(name: 'idx_res_status', columns: ['status'])]
#[ORM\Index(name: 'idx_res_created', columns: ['created_at'])]
#[ORM\HasLifecycleCallbacks]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $uuid;

    #[ORM\Column(length: 30, unique: true)]
    private ?string $reservationNumber = '';

    #[ORM\ManyToOne(targetEntity: Vehicle::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(length: 160)]
    private ?string $customerName = '';

    #[ORM\Column(length: 190)]
    private ?string $customerEmail = '';

    #[ORM\Column(length: 40)]
    private ?string $customerPhone = '';

    #[ORM\Column(length: 20, enumType: ReceptionMode::class)]
    private ReceptionMode $receptionMode = ReceptionMode::PICKUP;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $pickupFirstName = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $pickupLastName = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $pickupDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $deliveryDate = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $pickupTime = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $deliveryTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contrat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contratMimeType = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $deliveryCity = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $deliveryPostalCode = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private ?string $vehiclePriceSnapshot = '0.00';

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private ?string $reservationFeePercentage = '20.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private ?string $reservationFeeAmount = '0.00';

    #[ORM\Column(length: 30, enumType: PaymentMethod::class)]
    private PaymentMethod $paymentMethod = PaymentMethod::BANK_TRANSFER;

    #[ORM\Column(length: 40, enumType: ReservationStatus::class)]
    private ReservationStatus $status = ReservationStatus::PENDING_PAYMENT;

    #[ORM\Column(length: 40, enumType: PaymentStatus::class)]
    private PaymentStatus $paymentStatus = PaymentStatus::PENDING;

    #[ORM\OneToMany(mappedBy: 'reservation', targetEntity: Payment::class, orphanRemoval: true)]
    private Collection $payments;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    /** @var Collection<int,PaymentProof> */
    #[ORM\OneToMany(targetEntity: PaymentProof::class, mappedBy: 'reservation', cascade: ['persist','remove'])]
    #[ORM\OrderBy(['uploadedAt' => 'DESC'])]
    private Collection $paymentProofs;

    public function __construct()
    {
        $this->uuid = Uuid::v7()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->paymentProofs = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getPaymentReussi(): ?Payment
    {
        foreach ($this->payments as $payment) {
            if ($payment->getStatus() === 'paid') {
                return $payment;
            }
        }
        return null;
    }

    public function getId(): ?int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getReservationNumber(): ?string { return $this->reservationNumber; }
    public function setReservationNumber(?string $v): self { $this->reservationNumber = $v; return $this; }
    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(Vehicle $v): self { $this->vehicle = $v; return $this; }
    public function getCustomerName(): ?string { return $this->customerName; }
    public function setCustomerName(?string $v): self { $this->customerName = $v; return $this; }
    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    public function setCustomerEmail(?string $v): self { $this->customerEmail = $v; return $this; }
    public function getCustomerPhone(): ?string { return $this->customerPhone; }
    public function setCustomerPhone(?string $v): self { $this->customerPhone = $v; return $this; }

    public function getReceptionMode(): ReceptionMode { return $this->receptionMode; }
    public function setReceptionMode(ReceptionMode $v): self { $this->receptionMode = $v; return $this; }

    public function getContrat(): ?string { return $this->contrat; }
    public function setContrat(?string $v): self { $this->contrat = $v; return $this; }

    public function getContratMimeType(): ?string { return $this->contratMimeType; }
    public function setContratMimeType(?string $v): self { $this->contratMimeType = $v; return $this; }

    public function getPickupFirstName(): ?string { return $this->pickupFirstName; }
    public function setPickupFirstName(?string $v): self { $this->pickupFirstName = $v; return $this; }
    public function getPickupLastName(): ?string { return $this->pickupLastName; }
    public function setPickupLastName(?string $v): self { $this->pickupLastName = $v; return $this; }
    public function getPickupDate(): ?\DateTimeImmutable { return $this->pickupDate; }
    public function setPickupDate(?\DateTimeImmutable $v): self { $this->pickupDate = $v; return $this; }
    public function getDeliveryDate(): ?\DateTimeImmutable { return $this->deliveryDate; }
    public function setDeliveryDate(?\DateTimeImmutable $v): self { $this->deliveryDate = $v; return $this; }
    public function getPickupTime(): ?string { return $this->pickupTime; }
    public function setPickupTime(?string $v): self { $this->pickupTime = $v; return $this; }
    public function getDeliveryTime(): ?string { return $this->deliveryTime; }
    public function setDeliveryTime(?string $v): self { $this->deliveryTime = $v; return $this; }

    public function getDeliveryAddress(): ?string { return $this->deliveryAddress; }
    public function setDeliveryAddress(?string $v): self { $this->deliveryAddress = $v; return $this; }
    public function getDeliveryCity(): ?string { return $this->deliveryCity; }
    public function setDeliveryCity(?string $v): self { $this->deliveryCity = $v; return $this; }
    public function getDeliveryPostalCode(): ?string { return $this->deliveryPostalCode; }
    public function setDeliveryPostalCode(?string $v): self { $this->deliveryPostalCode = $v; return $this; }

    public function getVehiclePriceSnapshot(): ?string { return $this->vehiclePriceSnapshot; }
    public function setVehiclePriceSnapshot(?string $v): self { $this->vehiclePriceSnapshot = $v; return $this; }
    public function getReservationFeePercentage(): ?string { return $this->reservationFeePercentage; }
    public function setReservationFeePercentage(?string $v): self { $this->reservationFeePercentage = $v; return $this; }
    public function getReservationFeeAmount(): ?string { return $this->reservationFeeAmount; }
    public function setReservationFeeAmount(?string $v): self { $this->reservationFeeAmount = $v; return $this; }

    public function getPaymentMethod(): PaymentMethod { return $this->paymentMethod; }
    public function setPaymentMethod(PaymentMethod $v): self { $this->paymentMethod = $v; return $this; }
    public function getStatus(): ReservationStatus { return $this->status; }
    public function setStatus(ReservationStatus $v): self { $this->status = $v; return $this; }

    public function getPaymentStatus(): PaymentStatus { return $this->paymentStatus; }
    public function setPaymentStatus(PaymentStatus $v): self { $this->paymentStatus = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getConfirmedAt(): ?\DateTimeImmutable { return $this->confirmedAt; }
    public function setConfirmedAt(?\DateTimeImmutable $v): self { $this->confirmedAt = $v; return $this; }

    /** @return Collection<int,PaymentProof> */
    public function getPaymentProofs(): Collection { return $this->paymentProofs; }

    public function getLatestPaymentProof(): ?PaymentProof
    {
        return $this->paymentProofs->first() ?: null;
    }

    public function hasPaymentProof(): bool { return !$this->paymentProofs->isEmpty(); }
}
