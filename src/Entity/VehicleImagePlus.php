<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VehicleImagePlusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleImagePlusRepository::class)]
#[ORM\Table(name: 'vehicle_images_plus')]
class VehicleImagePlus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Vehicle::class, inversedBy: 'imagesPlus')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(length: 190)]
    private string $filename = '';

    #[ORM\Column(length: 190)]
    private string $originalFilename = '';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $primaryImage = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $v): self { $this->vehicle = $v; return $this; }
    public function getFilename(): string { return $this->filename; }
    public function setFilename(string $v): self { $this->filename = $v; return $this; }
    public function getOriginalFilename(): string { return $this->originalFilename; }
    public function setOriginalFilename(string $v): self { $this->originalFilename = $v; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $v): self { $this->position = $v; return $this; }
    public function isPrimaryImage(): bool { return $this->primaryImage; }
    public function setPrimaryImage(bool $v): self { $this->primaryImage = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getPublicPath(): string { return '/uploads/vehicles/plus/'.$this->filename; }
}
