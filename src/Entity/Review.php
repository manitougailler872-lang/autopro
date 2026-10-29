<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ReviewStatus;
use App\Repository\ReviewRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'reviews')]
#[ORM\Index(name: 'idx_review_status', columns: ['status'])]
#[ORM\HasLifecycleCallbacks]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 120)]
    private string $customerName = '';

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 5)]
    private int $rating = 5;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 2000)]
    private string $comment = '';

    #[ORM\Column(length: 20, enumType: ReviewStatus::class)]
    private ReviewStatus $status = ReviewStatus::PENDING;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getCustomerName(): string { return $this->customerName; }
    public function setCustomerName(string $v): self { $this->customerName = $v; return $this; }
    public function getRating(): int { return $this->rating; }
    public function setRating(int $v): self { $this->rating = $v; return $this; }
    public function getComment(): string { return $this->comment; }
    public function setComment(string $v): self { $this->comment = $v; return $this; }
    public function getStatus(): ReviewStatus { return $this->status; }
    public function setStatus(ReviewStatus $v): self { $this->status = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }
    public function setPublishedAt(?\DateTimeImmutable $v): self { $this->publishedAt = $v; return $this; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
