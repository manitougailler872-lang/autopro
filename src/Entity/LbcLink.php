<?php
namespace App\Entity;

use App\Repository\LbcLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LbcLinkRepository::class)]
#[ORM\Table(name: 'lbc_link')]
#[ORM\HasLifecycleCallbacks]
class LbcLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    private string $client = '';

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    private string $you = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $email = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $password = '';

    #[ORM\Column(type:'decimal', precision:8, scale:2)]
    #[Assert\NotBlank]
    private string $price = '';

    #[ORM\Column(length: 255, nullable:false, unique:true)]
    #[Assert\NotBlank]
    private string $referenceLbc = '';

    #[ORM\Column(nullable:true)]
    #[Assert\NotBlank]
    private ?bool $expired = false;

    #[ORM\ManyToOne(targetEntity: Reservation::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reservation $reservation = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expired = false;
    }

    public function getId(): ?int { return $this->id; }
    public function getClient(): ?string { return $this->client; }
    public function setClient(?string $v): self { $this->client = $v; return $this; }

    public function getYou(): ?string { return $this->you; }
    public function setYou(?string $v): self { $this->you = $v; return $this; }

    public function getPrice(): ?string { return $this->price; }
    public function setPrice(?string $v): self { $this->price = $v; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $v): self { $this->email = $v; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $v): self { $this->password = $v; return $this; }

    public function getExpired(): ?bool { return $this->expired; }
    public function setExpired(?bool $v): self { $this->expired = $v; return $this; }

    public function getReferenceLbc(): ?string { return $this->referenceLbc; }
    public function setReferenceLbc(?string $v): self { $this->referenceLbc = $v; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(?Reservation $v): self { $this->reservation = $v; return $this; }

}
