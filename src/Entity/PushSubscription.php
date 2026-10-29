<?php

declare(strict_types=1);
namespace App\Entity;

use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PushSubscriptionRepository::class)]
#[ORM\Table(name: 'push_notification')]
#[ORM\HasLifecycleCallbacks]
class PushSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $endpoint = '';
    
    #[ORM\Column(length: 255)]
    private string $publicKey = '';
    
    #[ORM\Column(length: 255)]
    private string $authToken = '';

    #[ORM\Column(length: 255)]
    private ?bool $expired = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getEndpoint(): ?string { return $this->endpoint; }
    public function getExpired(): ?string { return $this->expired; }
    public function getPublicKey(): ?string { return $this->publicKey; }
    public function getAuthToken(): ?string { return $this->authToken; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function setEndpoint(?string $v): static{
        $this->endpoint = $v;
        return $this;
    }
    public function setPublicKey(?string $v): static{
        $this->publicKey = $v;
        return $this;
    }
    public function setAuthToken(?string $v): static{
        $this->authToken = $v;
        return $this;
    }
    public function setExpired(?bool $v): static{
        $this->expired = $v;
        return $this;
    }
}
