<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\MailType;
use App\Repository\MailRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MailRepository::class)]
#[ORM\Table(name: 'mail')]
#[ORM\HasLifecycleCallbacks]
class Mail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'guid', unique: false)]
    private ?string $uuid;

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $email = '';

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $contrat = '';

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $dateEtablissementContrat = '';

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $heureContrat = '';

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $minuteContrat = '';

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $customerAddress = '';

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $customerCP = '';

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $customerCity = '';

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $dateOfBirth = '';

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $priceConcluded = '';

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $cardIdNumber = '';

    #[ORM\Column(enumType:MailType::class)]
    #[Assert\NotBlank]
    private ?MailType $mailType  = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Reservation $reservation = null;

    #[ORM\ManyToOne(targetEntity: Vehicle::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Vehicle $vehicle = null;

    #[ORM\Column]
    private bool $smsNotifierEnable = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->uuid = Uuid::v7()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate] public function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getUuid(): ?string { return $this->uuid; }
    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $v): self { $this->vehicle = $v; return $this; }

    public function getMailType(): ?MailType { return $this->mailType; }
    public function setMailType(?MailType $v): self { $this->mailType = $v; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $v): self { $this->email = $v; return $this; }
    
    public function getMinuteContrat(): ?string { return $this->minuteContrat; }
    public function setMinuteContratl(?string $v): self { $this->minuteContrat = $v; return $this; }

    public function getPriceConcluded(): ?string { return $this->priceConcluded; }
    public function setPriceConcludedl(?string $v): self { $this->priceConcluded = $v; return $this; }

    public function getContrat(): ?string { return $this->contrat; }
    public function setContrat(?string $v): self { $this->contrat = $v; return $this; }
    public function getDateEtablissementContrat(): ?string { return $this->dateEtablissementContrat; }
    public function setDateEtablissementContrat(?string $v): self { $this->dateEtablissementContrat = $v; return $this; }
    public function getHeureContrat(): ?string { return $this->heureContrat; }
    public function setHeureContrat(?string $v): self { $this->heureContrat = $v; return $this; }
    public function getCustomerAddress(): ?string { return $this->customerAddress; }
    public function setCustomerAddress(?string $v): self { $this->customerAddress = $v; return $this; }
    public function getCustomerCP(): ?string { return $this->customerCP; }
    public function setCustomerCP(?string $v): self { $this->customerCP = $v; return $this; }
    public function getCustomerCity(): ?string { return $this->customerCity; }
    public function setCustomerCity(?string $v): self { $this->customerCity = $v; return $this; }
    public function getDateOfBirth(): ?string { return $this->dateOfBirth; }
    public function setDateOfBirth(?string $v): self { $this->dateOfBirth = $v; return $this; }
    public function getCardIdNumber(): ?string { return $this->cardIdNumber; }
    public function setCardIdNumber(?string $v): self { $this->cardIdNumber = $v; return $this; }
    
    public function getSmsNotifierEnable(): ?bool { return $this->smsNotifierEnable; }
    public function setSmsNotifierEnable(?bool $v): self { $this->smsNotifierEnable = $v; return $this; }

    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(?Reservation $v): self { $this->reservation = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
