<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // ----------------- Champs -----------------
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

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Commande::class)]
    private Collection $commandes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Emprunt::class)]
    private Collection $emprunts;

    #[ORM\OneToMany(mappedBy: 'bibliothecaire', targetEntity: Emprunt::class)]
    private Collection $empruntsValides;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    // ----------------- Constructeur -----------------
    public function __construct()
    {
        $this->commandes = new ArrayCollection();
        $this->emprunts = new ArrayCollection();
        $this->empruntsValides = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
    }

    // ----------------- Security -----------------
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER'; // rôle par défaut
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Efface les données temporaires sensibles si nécessaire
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    // ----------------- Getters / Setters -----------------
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    // ----------------- Collections -----------------
    /** @return Collection<int, Commande> */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): self
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setUser($this);
        }
        return $this;
    }

    public function removeCommande(Commande $commande): self
    {
        if ($this->commandes->removeElement($commande)) {
            if ($commande->getUser() === $this) {
                $commande->setUser(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Emprunt> */
    public function getEmprunts(): Collection
    {
        return $this->emprunts;
    }

    public function addEmprunt(Emprunt $emprunt): self
    {
        if (!$this->emprunts->contains($emprunt)) {
            $this->emprunts->add($emprunt);
            $emprunt->setUser($this);
        }
        return $this;
    }

    public function removeEmprunt(Emprunt $emprunt): self
    {
        if ($this->emprunts->removeElement($emprunt)) {
            if ($emprunt->getUser() === $this) {
                $emprunt->setUser(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Emprunt> */
    public function getEmpruntsValides(): Collection
    {
        return $this->empruntsValides;
    }

    public function addEmpruntValide(Emprunt $emprunt): self
    {
        if (!$this->empruntsValides->contains($emprunt)) {
            $this->empruntsValides->add($emprunt);
            $emprunt->setBibliothecaire($this);
        }
        return $this;
    }

    public function removeEmpruntValide(Emprunt $emprunt): self
    {
        if ($this->empruntsValides->removeElement($emprunt)) {
            if ($emprunt->getBibliothecaire() === $this) {
                $emprunt->setBibliothecaire(null);
            }
        }
        return $this;
    }
}
