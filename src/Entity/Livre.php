<?php

namespace App\Entity;

use App\Repository\LivreRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: LivreRepository::class)]
class Livre
{
    // ----------------- Champs -----------------
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[Gedmo\Slug(fields: ['titre'])]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column]
    private float $prix;

    #[ORM\Column(length: 20)]
    private string $type; // physique | pdf

    #[ORM\Column(nullable: true)]
    private ?int $stock = null;

    #[ORM\ManyToOne(inversedBy: 'livres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $categorie = null;

    #[ORM\OneToMany(mappedBy: 'livre', targetEntity: Emprunt::class)]
    private Collection $emprunts;

    #[ORM\OneToMany(mappedBy: 'livre', targetEntity: LigneCommande::class)]
    private Collection $ligneCommandes;

    #[ORM\OneToOne(mappedBy: 'livre', targetEntity: LivrePdf::class, cascade: ['persist', 'remove'])]
    private ?LivrePdf $pdf = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    // ----------------- Constructeur -----------------
    public function __construct()
    {
        $this->emprunts = new ArrayCollection();
        $this->ligneCommandes = new ArrayCollection();
    }

    // ----------------- Getters / Setters -----------------
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrix(): float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): self
    {
        $this->stock = $stock;
        return $this;
    }

    public function getCategorie(): ?Category
    {
        return $this->categorie;
    }

    public function setCategorie(?Category $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getPdf(): ?LivrePdf
    {
        return $this->pdf;
    }

    public function setPdf(?LivrePdf $pdf): self
    {
        $this->pdf = $pdf;

        if ($pdf && $pdf->getLivre() !== $this) {
            $pdf->setLivre($this);
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    // ----------------- Collections -----------------
    /** @return Collection<int, Emprunt> */
    public function getEmprunts(): Collection
    {
        return $this->emprunts;
    }

    public function addEmprunt(Emprunt $emprunt): self
    {
        if (!$this->emprunts->contains($emprunt)) {
            $this->emprunts->add($emprunt);
            $emprunt->setLivre($this);
        }
        return $this;
    }

    public function removeEmprunt(Emprunt $emprunt): self
    {
        if ($this->emprunts->removeElement($emprunt)) {
            if ($emprunt->getLivre() === $this) {
                $emprunt->setLivre(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, LigneCommande> */
    public function getLigneCommandes(): Collection
    {
        return $this->ligneCommandes;
    }

    public function addLigneCommande(LigneCommande $ligneCommande): self
    {
        if (!$this->ligneCommandes->contains($ligneCommande)) {
            $this->ligneCommandes->add($ligneCommande);
            $ligneCommande->setLivre($this);
        }
        return $this;
    }

    public function removeLigneCommande(LigneCommande $ligneCommande): self
    {
        if ($this->ligneCommandes->removeElement($ligneCommande)) {
            if ($ligneCommande->getLivre() === $this) {
                $ligneCommande->setLivre(null);
            }
        }
        return $this;
    }
}
