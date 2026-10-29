<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\FuelType;
use App\Repository\VehicleRepository;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

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

    #[ORM\Column(length: 80,nullable: true,)]
    private ?string $brand = '';

    #[ORM\Column(type:'text',nullable:true)]
    private ?string $phoneCallInformation = '';

    #[ORM\Column(type:'integer',nullable:true)]
    private ?int $nbOwner;

    #[ORM\Column(type:'boolean',nullable:true)]
    private ?bool $hasTrace = false;

    #[ORM\Column(type:'text',nullable:true)]
    private ?string $technicalInformation = '';

    #[ORM\Column(length: 255,nullable: true,)]
    private ?string $firstImmatriculationDate;

    #[ORM\Column(length: 120,nullable: true,)]
    private ?string $emissionClass;

    #[ORM\Column(length: 255,nullable: true,)]
    private ?string $co2;

    #[ORM\Column(length: 255,nullable: true,)]
    private ?string $vim;

    #[ORM\Column(length: 255,nullable: true,)]
    private ?string $immatriculation;
    
    #[ORM\Column(length: 80,nullable: true,)]

    private ?string $model = '';

    #[ORM\Column(length: 255, nullable: true, type:'string')]

    private ?string $originNumber = '';

    #[ORM\Column(length: 120, unique: true)]

    private ?string $slug = '';

    #[ORM\Column(unique: false,nullable:true)]
    private ?int $gearbox;

    #[ORM\Column(length: 120, nullable:true)]
    private ?string $capacity;

    #[ORM\Column(length: 120, nullable: true)]

    private ?string $cylinder = '';

    #[ORM\Column(length: 120, nullable: true)]

    private ?string $systemDeFreinage = '';

    #[ORM\Column(length: 120, nullable: true)]

    private ?string $width = '';

    #[ORM\Column(length: 120, nullable: true)]

    private ?string $widthTwo = '';

    #[ORM\Column(length: 120, nullable: true)]

    private ?string $height = '';

    #[ORM\Column(length: 120, nullable: true)]

    private ?string $heightTwo = '';

    #[ORM\Column(length: 255, nullable: true)]

    private ?string $thickness = '';

    #[ORM\Column(length: 255, nullable: true)]

    private ?string $compatibility = '';

    #[ORM\Column(length: 120, nullable: true)]

    private ?string $thicknessTwo = '';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $wvaNumber = '';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $material;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $ownerReference;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $conformityBrand;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $complementary;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $version = null;

    #[ORM\Column(type: 'text', nullable: true)]

    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]

    private ?string $interior = null;

    #[ORM\Column(type: 'string', length:255, nullable: true)]
    private ?string $vehicleType = null;

    #[ORM\Column(type: 'text', nullable: true)]

    private ?string $exterior = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $security = null;

    #[ORM\Column(type: 'text', nullable: true)]

    private ?string $history = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $dateEntretien = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $heureEntretien = null;

    #[ORM\Column(type: 'text', nullable: true)]

    private ?string $equipments = null;

    #[ORM\Column(type: 'string', nullable: true)]

    private ?string $ean = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $garnitureMaterial;

    #[ORM\Column(nullable: true)]

    private ?int $year = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mileage = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $ctValidity = null;

    #[ORM\Column(type: 'string', length:255, nullable: true)]
    private ?string $ctFile = null;

    #[ORM\Column(type: 'string', length:255, nullable: true)]
    private ?string $rapportEntretien = null;

    #[ORM\Column(length: 20, enumType: FuelType::class,nullable: true)]

    private ?FuelType $fuel = FuelType::PETROL;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]

    private ?string $price = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]

    private ?string $financialPrice = '0.00';

    #[ORM\Column(length: 30, options: ['default' => 'AVAILABLE'])]
    private ?string $status = 'AVAILABLE';

    #[ORM\Column(nullable:true)]
    private ?bool $published = false;

    #[ORM\Column(nullable:true)]
    private ?bool $minPrice = false;

    #[ORM\Column(nullable:true)]
    private ?bool $showInFirst = false;

    #[ORM\Column(nullable:true)]
    private ?bool $newCollection = false;

    // ---------- NEW FIELDS (per client feedback) ----------
    #[ORM\Column(nullable: true)]
    private ?string $power = null;

    #[ORM\Column(nullable: true)]
    private ?string $powerCv = null;

    #[ORM\Column(nullable: true)]
    private ?string $datePremiereMiseEnCirculation = null;

    #[ORM\Column(nullable: true)]
    private ?int $places = null;

    #[ORM\Column(nullable: true)]
    private ?bool $existenceVerifiee = null;

    #[ORM\Column(nullable: true)]
    private ?bool $aucunSinistre = null;

    #[ORM\Column(nullable: true)]
    private ?bool $usageProfessionnel = null;

    #[ORM\Column(nullable: true)]

    private ?bool  $bestDeal = false;
    
    #[ORM\Column(nullable: true)]

    private ?bool  $isAuction = false;

    #[ORM\Column(nullable: true)]

    private ?bool  $isAvailable = true;

    #[ORM\Column(nullable: true)]

    private ?bool  $reserved = true;

    #[ORM\Column(nullable: true)]

    private ?int  $bestDealPercent = null;

    #[ORM\Column(length: 40, nullable: true)]

    private ?string $color = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $limitValiditeControl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dimPneus = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $controlDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cnit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tvv = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $genreV = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $genreVGC = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $normeEuro = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $genreVEuro = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $classeEnvironnementale = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dimBatterie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $codeMoteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $interiorMail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $exteriorMail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $securityMail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $historiqueMail = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $kilometrageReleve = null;

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

    #[ORM\Column(nullable:true)]
    private ?\DateTime $auctionEndsAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int,VehicleImage> */
    #[ORM\OneToMany(targetEntity: VehicleImage::class, mappedBy: 'vehicle', cascade: ['persist','remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

        /** @var Collection<int,VehicleImagePlus> */
    #[ORM\OneToMany(targetEntity: VehicleImagePlus::class, mappedBy: 'vehicle', cascade: ['persist','remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $imagesPlus;

    /** @var Collection<int,VehiculeImagesMail> */
    #[ORM\OneToMany(targetEntity: VehiculeImagesMail::class, mappedBy: 'vehicle', cascade: ['persist','remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $imagesMail;

    public function __construct()
    {
        $number  = str_replace(':','',sprintf('%s%s%s%s', (new \DateTime('now'))->format('d'),(new \DateTime('now'))->format('m'),(new \DateTime('now'))->format('Y'),(new \DateTime('now'))->format('H:m')));
        $this->uuid = Uuid::v7()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->originNumber = strtoupper(substr(md5($number),0,6));
        $this->updatedAt = new \DateTimeImmutable();
        $this->images = new ArrayCollection();
    }

    #[ORM\PreUpdate] public function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getUuid(): ?string { return $this->uuid; }
    public function getBrand(): ?string { return $this->brand; }

    /**
     * @return string|null
     */
    public function getCompatibility()
    {
        return $this->compatibility;
    }

    /**
     * @return string|null
     */
    public function getGarnitureMaterial()
    {
        return $this->garnitureMaterial;
    }

    /**
     * @return string|null
     */
    public function getConformityBrand()
    {
        return $this->conformityBrand;
    }

    /**
     * @return string|null
     */
    public function getComplementary()
    {
        return $this->complementary;
    }

    /**
     * @return string|null
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return string|null
     */
    public function getHeightTwo()
    {
        return $this->heightTwo;
    }

    /**
     * @return string|null
     */
    public function getMaterial()
    {
        return $this->material;
    }

    /**
     * @return string|null
     */
    public function getOriginNumber()
    {
        return $this->originNumber;
    }

    /**
     * @return string|null
     */
    public function getWidthTwo()
    {
        return $this->widthTwo;
    }

    /**
     * @return string|null
     */
    public function getWvaNumber()
    {
        return $this->wvaNumber;
    }

    /**
     * @return string|null
     */
    public function getOwnerReference()
    {
        return $this->ownerReference;
    }

    /**
     * @param string|null $compatibility
     */
    public function setCompatibility($compatibility)
    {
        $this->compatibility = $compatibility;
    }

    /**
     * @param string|null $complementary
     */
    public function setComplementary($complementary)
    {
        $this->complementary = $complementary;
    }

    /**
     * @param string|null $conformityBrand
     */
    public function setConformityBrand($conformityBrand)
    {
        $this->conformityBrand = $conformityBrand;
    }

    /**
     * @param string|null $garnitureMaterial
     */
    public function setGarnitureMaterial($garnitureMaterial)
    {
        $this->garnitureMaterial = $garnitureMaterial;
    }

    /**
     * @param string|null $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @param string|null $heightTwo
     */
    public function setHeightTwo($heightTwo)
    {
        $this->heightTwo = $heightTwo;
    }

    /**
     * @param Collection $images
     */
    public function setImages($images)
    {
        $this->images = $images;
    }

    /**
     * @param string|null $material
     */
    public function setMaterial($material)
    {
        $this->material = $material;
    }

    /**
     * @param string|null $originNumber
     */
    public function setOriginNumber($originNumber)
    {
        $this->originNumber = $originNumber;
    }

    /**
     * @param string|null $ownerReference
     */
    public function setOwnerReference($ownerReference)
    {
        $this->ownerReference = $ownerReference;
    }

    /**
     * @param string|null $widthTwo
     */
    public function setWidthTwo($widthTwo)
    {
        $this->widthTwo = $widthTwo;
    }

    /**
     * @param string|null $wvaNumber
     */
    public function setWvaNumber($wvaNumber)
    {
        $this->wvaNumber = $wvaNumber;
    }

    /**
     * @param string|null $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string|null
     */
    public function getEan()
    {
        return $this->ean;
    }

    /**
     * @param string|null $ean
     */
    public function setEan($ean)
    {
        $this->ean = $ean;
    }

    public function getVehicleType(): ?string { return $this->vehicleType; }
    public function setVehicleType(?string $v): self { $this->vehicleType = $v; return $this; }

    public function getCylinder(): ?string { return $this->cylinder; }
    public function setCylinder(?string $v): self { $this->cylinder = $v; return $this; }

    public function getThicknessTwo(): ?string { return $this->thicknessTwo; }
    public function setThicknessTwo(?string $v): self { $this->thicknessTwo = $v; return $this; }

    public function getThickness(): ?string { return $this->thickness; }
    public function setThickness(?string $v): self { $this->thickness = $v; return $this; }

    public function getWidth(): ?string { return $this->width; }
    public function setWidth(?string $v): self { $this->width = $v; return $this; }

    public function getCtFile(): ?string { return $this->ctFile; }
    public function setCtFile(?string $v): self { $this->ctFile = $v; return $this; }

    public function getRapportEntretien(): ?string { return $this->rapportEntretien; }
    public function setRapportEntretien(?string $v): self { $this->rapportEntretien = $v; return $this; }

    public function getSystemDeFreinage(): ?string { return $this->systemDeFreinage; }
    public function setSystemDeFreinage(?string $v): self { $this->systemDeFreinage = $v; return $this; }

    public function getBestDeal(): ?bool {return  $this->bestDeal ?? false; }
    public function setBestDeal(bool $v):self
    {
        $this->bestDeal = $v;
        return $this;
    }

    public function getPlaces(): ?int {return  $this->places ?? null; }
    public function setPlaces(?int $v):self
    {
        $this->places = $v;
        return $this;
    }

    public function getPowerCv(): ?string { return $this->powerCv; }
    public function setPowerCv(?string $v): self { $this->powerCv = $v; return $this; }

    public function getReserved(): ?bool {return  $this->reserved ?? false; }
    public function setReserved(bool $v):self
    {
        $this->reserved = $v;
        return $this;
    }

    public function getShowInFirst(): ?bool {return  $this->showInFirst ?? false; }
    public function setShowInFirst(bool $v):self
    {
        $this->showInFirst = $v;
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

    public function getInteriorMail():?string{ return $this->interiorMail;}
    public function setInteriorMail(?string $v): self { $this->interiorMail = $v; return $this; }

    public function getExteriorMail():?string{ return $this->exteriorMail;}
    public function setExteriorMail(?string $v): self { $this->exteriorMail = $v; return $this; }

    public function getSecurityMail():?string{ return $this->securityMail;}
    public function setSecurityMail(?string $v): self { $this->securityMail = $v; return $this; }

    public function getHistoriqueMail():?string{ return $this->historiqueMail;}
    public function setHistoriqueMail(?string $v): self { $this->historiqueMail = $v; return $this; }

    public function setBrand(?string $v): self { $this->brand = $v; return $this; }
    public function getModel(): ?string { return $this->model; }
    public function setModel(?string $v): self { $this->model = $v; return $this; }
    public function getDimBatterie(): ?string { return $this->dimBatterie; }
    public function setDimBatterie(?string $v): self { $this->dimBatterie = $v; return $this; }
    public function getDateEntretien(): ?string { return $this->dateEntretien; }
    public function setDateEntretien(?string $v): self { $this->dateEntretien = $v; return $this; }
    public function getHeureEntretien(): ?string { return $this->heureEntretien; }
    public function setHeureEntretien(?string $v): self { $this->heureEntretien = $v; return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $v): self { $this->slug = $v; return $this; }
    public function getVersion(): ?string { return $this->version; }
    public function setVersion(?string $v): self { $this->version = $v; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): self { $this->description = $v; return $this; }
    public function getYear(): ?int { return $this->year; }
    public function setYear(?int $v): self { $this->year = $v; return $this; }
    public function getMileage(): ?int { return $this->mileage; }
    public function setMileage(?int $v): self { $this->mileage = $v; return $this; }

    public function getBestDealPercent(): ?int { return $this->bestDealPercent; }
    public function setBestDealPercent(?int $v): self { $this->bestDealPercent = $v; return $this; }

    public function getCtValidity(): ?string { return $this->ctValidity; }
    public function setCtValidity(?string $v): self { $this->ctValidity = $v; return $this; }

    public function getExistenceVerifiee(): ?bool { return $this->existenceVerifiee; }
    public function setExistenceVerifiee(?bool $v): self { $this->existenceVerifiee = $v; return $this; }

    public function getAucunSinistre(): ?bool { return $this->aucunSinistre; }
    public function setAucunSinistre(?bool $v): self { $this->aucunSinistre = $v; return $this; }

    public function getUsageProfessionnel(): ?bool { return $this->usageProfessionnel; }
    public function setUsageProfessionnel(?bool $v): self { $this->usageProfessionnel = $v; return $this; }

    public function getFuel(): ?FuelType { return $this->fuel; }
    public function setFuel(?FuelType $v): self { $this->fuel = $v; return $this; }
    public function getPrice(): string { return $this->price; }
    public function getPriceAsFloat(): float { return (float) $this->price; }
    public function setPrice(string|float $v): self { $this->price = is_float($v) ? number_format($v, 2, '.', '') : $v; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $v): self { $this->status = $v; return $this; }
    public function isPublished(): bool { return $this->published; }
    public function setPublished(bool $v): self { $this->published = $v; return $this; }
    public function getPower(): ?string { return $this->power; }
    public function setPower(?string $v): self { $this->power = $v; return $this; }
    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $v): self { $this->color = $v; return $this; }
    public function getDoors(): ?int { return $this->doors; }
    public function setDoors(?int $v): self { $this->doors = $v; return $this; }
    public function getTransmission(): ?string { return $this->transmission; }
    public function setTransmission(?string $v): self { $this->transmission = $v; return $this; }
    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $v): self { $this->rating = $v; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $v): self { $this->category = $v; return $this; }
    public function getNbOwner(): ?int { return $this->nbOwner; }
    public function setNbOwner(?int $v): self { $this->nbOwner = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function getCnit(): ?string { return $this->cnit; }
    public function setCnit(?string $v): self { $this->cnit = $v; return $this; }
    public function getDimPneus(): ?string { return $this->dimPneus; }
    public function setDimPneus(?string $v): self { $this->dimPneus = $v; return $this; }

    public function getTvv(): ?string { return $this->tvv; }
    public function setTvv(?string $v): self { $this->tvv = $v; return $this; }
    public function getGenreV(): ?string { return $this->genreV; }
    public function setGenreV(?string $v): self { $this->genreV = $v; return $this; }
    public function getGenreVGC(): ?string { return $this->genreVGC; }
    public function setGenreVGC(?string $v): self { $this->genreVGC = $v; return $this; }
    public function getNormeEuro(): ?string { return $this->normeEuro; }
    public function setNormeEuro(?string $v): self { $this->normeEuro = $v; return $this; }
    public function getGenreVEuro(): ?string { return $this->genreVEuro; }
    public function setGenreVEuro(?string $v): self { $this->genreVEuro = $v; return $this; }
    public function getClasseEnvironnementale(): ?string { return $this->classeEnvironnementale; }
    public function setClasseEnvironnementale(?string $v): self { $this->classeEnvironnementale = $v; return $this; }
    public function getCodeMoteur(): ?string { return $this->codeMoteur; }
    public function setCodeMoteur(?string $v): self { $this->codeMoteur = $v; return $this; }

    public function getPhoneCallInformation(): ?string
    {
        return $this->phoneCallInformation;
    }

    public function setPhoneCallInformation(?string $phoneCallInformation): static
    {
        $this->phoneCallInformation = $phoneCallInformation;

        return $this;
    }


    public function getHasTrace(): ?bool
    {
        return $this->hasTrace ?? false;
    }

    public function setHasTrace(?bool $hasTrace): static
    {
        $this->hasTrace = $hasTrace;

        return $this;
    }

    public function setMinPrice(): ?bool
    {
        return $this->minPrice ?? false;
    }

    public function getMinPrice(?bool $minPrice): static
    {
        $this->minPrice = $minPrice;

        return $this;
    }

    public function setNewCollection(): ?bool
    {
        return $this->newCollection ?? false;
    }

    public function getNewCollection(?bool $newCollection): static
    {
        $this->newCollection = $newCollection;

        return $this;
    }


    public function getTechnicalInformation(): ?string
    {
        return $this->technicalInformation;
    }

    public function setTechnicalInformation(?string $technicalInformation): static
    {
        $this->technicalInformation = $technicalInformation;

        return $this;
    }


    public function getFirstImmatriculationDate(): ?string
    {
        return $this->firstImmatriculationDate;
    }

    public function setFirstImmatriculationDate(?string $firstImmatriculationDate): static
    {
        $this->firstImmatriculationDate = $firstImmatriculationDate;

        return $this;
    }


    public function getEmissionClass(): ?string
    {
        return $this->emissionClass;
    }

    public function setEmissionClass(?string $emissionClass): static
    {
        $this->emissionClass = $emissionClass;

        return $this;
    }


    public function getCo2(): ?string
    {
        return $this->co2;
    }

    public function setCo2(?string $co2): static
    {
        $this->co2 = $co2;

        return $this;
    }

    public function getLimitValiditeControl(): ?string { return $this->limitValiditeControl; }
    public function setLimitValiditeControl(?string $v): self { $this->limitValiditeControl = $v; return $this; }
    public function getControlDate(): ?string { return $this->controlDate; }
    public function setControlDate(?string $v): self { $this->controlDate = $v; return $this; }
    public function getKilometrageReleve(): ?string { return $this->kilometrageReleve; }
    public function setKilometrageReleve(?string $v): self { $this->kilometrageReleve = $v; return $this; }
    public function getImmatriculation(): ?string { return $this->immatriculation; }
    public function setImmatriculation(?string $v): self { $this->immatriculation = $v; return $this; }
    public function getDatePremiereMiseEnCirculation(): ?string { return $this->datePremiereMiseEnCirculation; }
    public function setDatePremiereMiseEnCirculation(?string $v): self { $this->datePremiereMiseEnCirculation = $v; return $this; }


    public function getVim(): ?string
    {
        return $this->vim;
    }

    public function setVim(?string $vim): static
    {
        $this->vim = $vim;

        return $this;
    }


    public function getGearbox(): ?int
    {
        return $this->gearbox;
    }

    public function setGearbox(?int $gearbox): static
    {
        $this->gearbox = $gearbox;

        return $this;
    }


    public function getCapacity(): ?string
    {
        return $this->capacity;
    }

    public function setCapacity(?string $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }


    public function getFinancialPrice(): ?string
    {
        return $this->financialPrice;
    }

    public function setFinancialPrice(?string $financialPrice): static
    {
        $this->financialPrice = $financialPrice;

        return $this;
    }


    public function getIsAuction(): ?bool
    {
        return $this->isAuction ?? false;
    }

    public function setIsAuction(?bool $isAuction): static
    {
        $this->isAuction = $isAuction;

        return $this;
    }

    public function getAuctionEndsAt(): \DateTime {
        if($this->isAuction) {
            return (new \DateTime('now'))->modify('+ 10 days');
        }
        return (new \DateTime('now'));
     }

    /** @return Collection<int,VehicleImage> */
    public function getImages(): Collection { return $this->images; }

    /** @return Collection<int,VehicleImage> */
    public function getImagesPlus(): Collection { return $this->imagesPlus; }

        /** @return Collection<int,VehicleImage> */
    public function getImagesMail(): Collection { return $this->imagesMail; }


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

    public function addImagePlus(VehicleImagePlus $img): self
    {
        if (!$this->imagesPlus->contains($img)) {
            $this->imagesPlus->add($img);
            $img->setVehicle($this);
        }
        return $this;
    }

    public function addImageMail(VehiculeImagesMail $img): self
    {
        if (!$this->imagesMail->contains($img)) {
            $this->imagesMail->add($img);
            $img->setVehicle($this);
        }
        return $this;
    }

    public function removeImage(VehicleImage $img): self { $this->images->removeElement($img); return $this; }
    public function removeImagePlus(VehicleImagePlus $img): self { $this->imagesPlus->removeElement($img); return $this; }
    public function removeImageMail(VehiculeImagesMail $img): self { $this->imagesMail->removeElement($img); return $this; }
    public function getFullName(): string { return trim(sprintf('%s %s %s', $this->brand, $this->model, $this->version ?? '')); }
}
