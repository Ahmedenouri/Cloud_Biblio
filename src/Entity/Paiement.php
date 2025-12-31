<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Paiement
{
    // ----------------- Champs -----------------
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private float $montant;

    #[ORM\Column(length: 50)]
    private string $methode;

    #[ORM\Column(length: 50)]
    private string $statut;

    #[ORM\OneToOne(inversedBy: 'paiement')]
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
        $this->statut = 'en_attente';
    }

    // ----------------- Getters / Setters -----------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    public function getMethode(): string
    {
        return $this->methode;
    }

    public function setMethode(string $methode): self
    {
        $this->methode = $methode;
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

    // ----------------- Relation -----------------

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): self
    {
        $this->commande = $commande;

        // Synchronisation côté Commande
        if ($commande && $commande->getPaiement() !== $this) {
            $commande->setPaiement($this);
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
