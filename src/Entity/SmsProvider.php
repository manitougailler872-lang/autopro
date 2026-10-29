<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ProviderType;
use App\Repository\SmsProviderRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SmsProviderRepository::class)]
#[ORM\Table(name: 'sms_providers')]
#[ORM\HasLifecycleCallbacks]
class SmsProvider
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType:ProviderType::class)]
    #[Assert\NotBlank]
    private ?ProviderType $provider = null;

    #[ORM\Column(type:'decimal', precision:14, scale:2,  nullable:true)]
    #[Assert\NotBlank]
    private ?string $credit =  '';

    #[ORM\Column(type:'string', length:255, nullable:true)]
    #[Assert\NotBlank]
    private ?string $baseUrl = '';

    #[ORM\Column(type:'boolean')]
    private ?bool $enable = true;

    public function __construct()
    {}

    public function getId(): ?int { return $this->id; }
    public function getProvider(): ?ProviderType { return $this->provider; }
    public function setProvider(?ProviderType $v): static {  $this->provider = $v; return $this;}
    public function getCredit(): ?string { return $this->credit; }
    public function setCredit(?string $v): static {  $this->credit = $v; return $this; }
    public function getBaseUrl(): ?string { return $this->baseUrl; }
    public function setBaseUrl(?string $v): static {  $this->baseUrl = $v; return $this; }
    public function getEnable(): ?string { return $this->enable; }
    public function setEnable(?bool $v): static {  $this->enable = $v; return $this; }

}
