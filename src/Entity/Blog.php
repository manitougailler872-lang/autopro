<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BlogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: BlogRepository::class)]
#[ORM\Table(name: 'blogs')]
#[ORM\Index(name: 'idx_blog_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class Blog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = '';

    #[ORM\Column(length: 100, unique: true)]
    private ?string $slug = '';

    #[ORM\Column(length: 255)]
    private ?string $shortDescription = '';

    #[ORM\Column(type:'text')]
    private ?string $description = '';

    #[ORM\Column(nullable: true)]
    private bool $showInFront = false;

    #[ORM\Column(nullable: true, type:'string', length:255)]
    private ?string $frontImage = null;

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

    #[ORM\PreUpdate] 
    public function touch(): void {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $v): self { $this->name = $v; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $v): self { $this->description = $v; return $this; }
    public function getShortDescription(): string { return $this->shortDescription; }
    public function setShortDescription(string $v): self { $this->shortDescription = $v; return $this; }
    public function getFrontImage(): ?string { return $this->frontImage; }
    public function setFrontImage(?string $v): self { $this->frontImage = $v; return $this; }
    public function getActive(): ?bool { return $this->active; }
    public function setActive(bool $v): self { $this->active = $v; return $this; }
    public function getShowInFront(): ?bool { return $this->showInFront; }
    public function setShowInFront(bool $v): self { $this->showInFront = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $v): self { $this->slug = $v; return $this; }
}
