<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profile = null;

    // --- NOUVEAUX CHAMPS RESEAUX SOCIAUX ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebook = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagram = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkedin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $x = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Commande::class)]
    private Collection $commandes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Emprunt::class)]
    private Collection $emprunts;

    #[ORM\OneToMany(mappedBy: 'bibliothecaire', targetEntity: Emprunt::class)]
    private Collection $empruntsValides;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->commandes = new ArrayCollection();
        $this->emprunts = new ArrayCollection();
        $this->empruntsValides = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
        $this->profile = 'default.png';
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // ... Getters & Setters existants (id, nom, email, password, roles, isVerified, etc.) ...

    public function getUserIdentifier(): string { return (string) $this->email; }
    public function getRoles(): array { $roles = $this->roles; $roles[] = 'ROLE_USER'; return array_unique($roles); }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }
    public function getPassword(): string { return (string) $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }
    public function eraseCredentials(): void {}
    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): static { $this->isVerified = $isVerified; return $this; }
    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $verificationToken): static { $this->verificationToken = $verificationToken; return $this; }
    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }
    public function getProfile(): ?string { return $this->profile; }
    public function setProfile(?string $profile): static { $this->profile = $profile; return $this; }

    // --- GETTERS & SETTERS RESEAUX SOCIAUX ---
    public function getFacebook(): ?string { return $this->facebook; }
    public function setFacebook(?string $facebook): static { $this->facebook = $facebook; return $this; }
    public function getInstagram(): ?string { return $this->instagram; }
    public function setInstagram(?string $instagram): static { $this->instagram = $instagram; return $this; }
    public function getLinkedin(): ?string { return $this->linkedin; }
    public function setLinkedin(?string $linkedin): static { $this->linkedin = $linkedin; return $this; }
    public function getX(): ?string { return $this->x; }
    public function setX(?string $x): static { $this->x = $x; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
}