<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UrlShorterRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid as UidUuid;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UrlShorterRepository::class)]
#[ORM\Table(name: 'url_shorter')]
#[ORM\HasLifecycleCallbacks]
class UrlShorter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type:'string', length:255, nullable:true)]
    #[Assert\NotBlank]
    private ?string $code = '';

    #[ORM\Column(type:'string', length:255, nullable:true)]
    #[Assert\NotBlank]
    private ?string $url = '';

    public function __construct()
    {
        $this->code = substr(md5(UidUuid::v4()->toString()), 0, 8);
    }

    public function getId(): ?int { return $this->id; }
    public function setCode(?string $v): static {  $this->code = $v; return $this; }
    public function getCode(): ?string { return $this->code; }
    public function setUrl(?string $v): static {  $this->url = $v; return $this; }
    public function getUrl(): ?string { return $this->url; }

}
