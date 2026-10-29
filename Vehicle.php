<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\FuelType;
use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
#[ORM\Table(name: 'vehicles')]
#[ORM\Index(name: 'idx_vehicle_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_vehicle_published', columns: ['published'])]
#[ORM\Index(name: 'idx_vehicle_brand', columns: ['brand'])]
#[ORM\Index(name: 'idx_vehicle_year', columns: ['year'])]
#[ORM\HasLifecycleCallbacks]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'guid', unique: false)]
    private ?string $uuid;

    #[ORM\Column(length: 80)]

    private ?string $brand = '';

    #[ORM\Column(length: 80)]

    private ?string $model = '';

    #[ORM\Column(length: 120, unique: true)]

    private ?string $slug = '';

    #[ORM\Column(length: 120, nullable: true)]

    private ?string $version = null;

    #[ORM\Column(type: 'text', nullable: true)]

    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]

    private ?string $interior = null;

    #[ORM\Column(type: 'text', nullable: true)]

    private ?string $exterior = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $security = null;

    #[ORM\Column(type: 'text', nullable: true)]

    private ?string $history = null;

    #[ORM\Column(type: 'text', nullable: true)]

    private ?string $equipments = null;

    #[ORM\Column]

    private int $year = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mileage = null;

    #[ORM\Column(length: 20, enumType: FuelType::class)]

    private FuelType $fuel = FuelType::PETROL;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]

    private ?string $price = '0.00';

    #[ORM\Column(length: 30, options: ['default' => 'AVAILABLE'])]
    private ?string $status = 'AVAILABLE';

    #[ORM\Column]

    private bool $published = false;

    // ---------- NEW FIELDS (per client feedback) ----------
    #[ORM\Column(nullable: true)]

    private ?int $power = null;

    #[ORM\Column(nullable: true)]

    private ?bool  $bestDeal = false;

    #[ORM\Column(nullable: true)]

    private ?bool  $isAvailable = true;

    #[ORM\Column(nullable: true)]

    private ?int  $bestDealPercent = null;

    #[ORM\Column(length: 40, nullable: true)]

    private ?string $color = null;

    #[ORM\Column(nullable: true)]

    private ?int $doors = null;

    #[ORM\Column(length: 30, nullable: true)]

    private ?string $transmission = null;

    #[ORM\Column(nullable: true)]

    private ?int $rating = null;

    #[ORM\Column(length: 30, nullable: true)]

    private ?string $category = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int,VehicleImage> */
    #[ORM\OneToMany(targetEntity: VehicleImage::class, mappedBy: 'vehicle', cascade: ['persist','remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    public function __construct()
    {
        $this->uuid = Uuid::v7()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->images = new ArrayCollection();
    }

    #[ORM\PreUpdate] public function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getUuid(): ?string { return $this->uuid; }
    public function getBrand(): ?string { return $this->brand; }

    public function getBestDeal(): ?bool {return  $this->bestDeal ?? false; }
    public function setBestDeal(bool $v):self
    {
        $this->bestDeal = $v;
        return $this;
    }

    public function getIsAvailable(): ?bool {return  $this->isAvailable ?? true; }
    public function setIsAvailable(bool $v):self
    {
        $this->isAvailable = $v;
        return $this;
    }


    public function getEquipments():?string{ return $this->equipments;}
    public function setEquipments(?string $v): self { $this->equipments = $v; return $this; }

    public function getHistory():?string{ return $this->history;}
    public function setHistory(?string $v): self { $this->history = $v; return $this; }

    public function getInterior():?string{ return $this->interior;}
    public function setInterior(?string $v): self { $this->interior = $v; return $this; }

    public function getSecurity():?string{ return $this->security;}
    public function setSecurity(?string $v): self { $this->security = $v; return $this; }

    public function getExterior():?string{ return $this->exterior;}
    public function setExterior(?string $v): self { $this->exterior = $v; return $this; }

    public function setBrand(?string $v): self { $this->brand = $v; return $this; }
    public function getModel(): ?string { return $this->model; }
    public function setModel(?string $v): self { $this->model = $v; return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $v): self { $this->slug = $v; return $this; }
    public function getVersion(): ?string { return $this->version; }
    public function setVersion(?string $v): self { $this->version = $v; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): self { $this->description = $v; return $this; }
    public function getYear(): int { return $this->year ?? 0; }
    public function setYear(int $v): self { $this->year = $v; return $this; }
    public function getMileage(): ?int { return $this->mileage ?? 0; }
    public function setMileage(?int $v): self { $this->mileage = $v; return $this; }

    public function getBestDealPercent(): ?int { return $this->bestDealPercent ?? 0; }
    public function setBestDealPercent(?int $v): self { $this->bestDealPercent = $v; return $this; }
    public function getFuel(): FuelType { return $this->fuel; }
    public function setFuel(FuelType $v): self { $this->fuel = $v; return $this; }
    public function getPrice(): string { return $this->price; }
    public function getPriceAsFloat(): float { return (float) $this->price; }
    public function setPrice(string|float $v): self { $this->price = is_float($v) ? number_format($v, 2, '.', '') : $v; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $v): self { $this->status = $v; return $this; }
    public function isPublished(): bool { return $this->published; }
    public function setPublished(bool $v): self { $this->published = $v; return $this; }
    public function getPower(): ?int { return $this->power ?? 0; }
    public function setPower(?int $v): self { $this->power = $v; return $this; }
    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $v): self { $this->color = $v; return $this; }
    public function getDoors(): ?int { return $this->doors ?? 0; }
    public function setDoors(?int $v): self { $this->doors = $v; return $this; }
    public function getTransmission(): ?string { return $this->transmission; }
    public function setTransmission(?string $v): self { $this->transmission = $v; return $this; }
    public function getRating(): ?int { return $this->rating ?? 0; }
    public function setRating(?int $v): self { $this->rating = $v; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $v): self { $this->category = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    /** @return Collection<int,VehicleImage> */
    public function getImages(): Collection { return $this->images; }

    public function getPrimaryImage(): ?VehicleImage
    {
        foreach ($this->images as $img) {
            if ($img->isPrimaryImage()) return $img;
        }
        return $this->images->first() ?: null;
    }

    public function addImage(VehicleImage $img): self
    {
        if (!$this->images->contains($img)) {
            $this->images->add($img);
            $img->setVehicle($this);
        }
        return $this;
    }

    public function removeImage(VehicleImage $img): self { $this->images->removeElement($img); return $this; }
    public function getFullName(): string { return trim(sprintf('%s %s %s', $this->brand, $this->model, $this->version ?? '')); }
}
