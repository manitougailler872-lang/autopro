<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SmsStatus;
use App\Repository\SmsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SmsRepository::class)]
#[ORM\Table(name: 'sms')]
#[ORM\HasLifecycleCallbacks]
class Sms
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type:'string', length:255, nullable:true)]
    #[Assert\NotBlank]
    private ?string $numero = '';

    #[ORM\Column(type:'string', length:255, nullable:true)]
    private ?string $referenceLbc = '';

     #[ORM\Column(type:'string', length:255, nullable:true)]
    private ?string $referenceSms = '';

    #[ORM\Column(type:'text', nullable:true)]
    #[Assert\NotBlank]
    private ?string $message = '';

    #[ORM\Column(type:'text', nullable:true)]
    #[Assert\NotBlank]
    private ?string $mailTo = '';

    #[ORM\Column(type:'string', length:255, nullable:true)]
    #[Assert\NotBlank]
    private ?string $name = '';

    #[ORM\Column(type:'string', length:255, nullable:true)]
    private ?string $url = '';

    #[ORM\ManyToOne(targetEntity: Expediteur::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Expediteur $expediteur = null;

    #[ORM\Column(enumType:SmsStatus::class)]
    private ?SmsStatus $status = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Reservation $reservation = null;

    #[ORM\ManyToOne(targetEntity: Vehicle::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Vehicle $vehicle = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate] public function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $v): self { $this->message = $v; return $this; }
    public function getReferenceLbc(): ?string { return $this->referenceLbc; }
    public function setReferenceLbc(?string $v): self { $this->referenceLbc = $v; return $this; }
    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $v): self { $this->url = $v; return $this; }
    public function getName(): ?string { return $this->name; }
    public function setName(?string $v): self { $this->name = $v; return $this; }
    public function getNumero(): ?string { return $this->numero; }
    public function setNumero(?string $v): self { $this->numero = $v; return $this; }
    public function getExpediteur(): ?Expediteur { return $this->expediteur; }
    public function setExpediteur(?Expediteur $v): self { $this->expediteur = $v; return $this; }
    public function getStatus(): ?SmsStatus { return $this->status; }
    public function setStatus(?SmsStatus $v): self { $this->status = $v; return $this; }
    public function getMailTo(): ?string { return $this->mailTo; }
    public function setMailTo(?string $v): self { $this->mailTo = $v; return $this; }
    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(?Reservation $v): self { $this->reservation = $v; return $this; }
    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $v): self { $this->vehicle = $v; return $this; }
    public function getReferenceSms(): ?string { return $this->referenceSms; }
    public function setReferenceSms(?string $v): self { $this->referenceSms = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function __toString(): string { return $this->message; }
}
