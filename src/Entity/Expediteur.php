<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ProviderType;
use App\Repository\ExpediteurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExpediteurRepository::class)]
#[ORM\Table(name: 'expediteurs')]
#[ORM\HasLifecycleCallbacks]
class Expediteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(enumType:ProviderType::class)]
    #[Assert\NotBlank]
    private ?ProviderType $provider = null;

    #[ORM\Column]
    private bool $active = true;

    public function __construct()
    {}

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $v): self { $this->name = $v; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $v): self { $this->active = $v; return $this; }
    public function getProvider(): ?ProviderType { return $this->provider; }
    public function setProvider(?ProviderType $v): static {  $this->provider = $v; return $this;}
}
