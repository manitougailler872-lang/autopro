<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BrandRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: BrandRepository::class)]
#[ORM\Table(name: 'brands')]
#[ORM\Index(name: 'idx_brand_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class Brand
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(length: 100, unique: true)]
    private string $slug = '';

    #[ORM\Column(nullable: true)]
    private bool $showInFrontend = false;

    #[ORM\Column(nullable: true)]
    private bool $forPiece = false;

    #[ORM\Column(nullable: true)]
    private bool $isModel = false;

    /** Uploaded logo filename (stored in public/uploads/brands) */
    #[ORM\Column(length: 190, nullable: true)]
    private ?string $logoFilename = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $active = true;

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
    public function getName(): string { return $this->name; }
    public function setName(string $v): self { $this->name = $v; return $this; }

    /**
     * @return bool
     */
    public function isForPiece() { return $this->forPiece;}

    /**
     * @return bool
     */
    public function isShowInFrontend(){ return $this->showInFrontend; }

    /**
     * @param bool $forPiece
     */
    public function setForPiece($forPiece){ $this->forPiece = $forPiece;}

    /**
     * @param bool $showInFrontend
     */
    public function setShowInFrontend($showInFrontend){$this->showInFrontend = $showInFrontend;}
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $v): self { $this->slug = $v; return $this; }
    public function getLogoFilename(): ?string { return $this->logoFilename; }
    public function setLogoFilename(?string $v): self { $this->logoFilename = $v; return $this; }
    public function getLogoPath(): ?string { return $this->logoFilename ? '/uploads/brands/'.$this->logoFilename : null; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $v): self { $this->position = $v; return $this; }
    public function getIsModel(): bool { return $this->isModel; }
    public function setIsModel(bool $v): self { $this->isModel = $v; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $v): self { $this->active = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function __toString(): string { return $this->name; }
}
