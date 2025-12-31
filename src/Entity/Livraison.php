<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Livraison
{
    // ----------------- Champs -----------------
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $adresse;

    #[ORM\Column(length: 50)]
    private string $statut;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $dateLivraison;

    #[ORM\OneToOne(inversedBy: 'livraison')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    // ----------------- Constructor -----------------
    public function __construct()
    {
        $this->dateLivraison = new \DateTime();
        $this->statut = 'en_preparation';
    }

    // ----------------- Getters / Setters -----------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdresse(): string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateLivraison(): \DateTimeInterface
    {
        return $this->dateLivraison;
    }

    public function setDateLivraison(\DateTimeInterface $dateLivraison): self
    {
        $this->dateLivraison = $dateLivraison;
        return $this;
    }

    // ----------------- Relation -----------------

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): self
    {
        $this->commande = $commande;

        // Synchronisation côté Commande
        if ($commande && $commande->getLivraison() !== $this) {
            $commande->setLivraison($this);
        }

        return $this;
    }

    // ----------------- Timestamps -----------------

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
