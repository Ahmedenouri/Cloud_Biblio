<?php

namespace App\Entity;

use App\Repository\EmpruntRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: EmpruntRepository::class)]
class Emprunt
{
    // ----------------- Champs -----------------
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $dateEmprunt;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $dateRetourPrevue;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateRetourEffective = null;

    #[ORM\Column(length: 50)]
    private string $statut;

    #[ORM\ManyToOne(inversedBy: 'emprunts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'emprunts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livre $livre = null;

    #[ORM\ManyToOne(inversedBy: 'empruntsValides')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $bibliothecaire = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    // ----------------- Getters / Setters -----------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEmprunt(): \DateTimeInterface
    {
        return $this->dateEmprunt;
    }

    public function setDateEmprunt(\DateTimeInterface $dateEmprunt): self
    {
        $this->dateEmprunt = $dateEmprunt;
        return $this;
    }

    public function getDateRetourPrevue(): \DateTimeInterface
    {
        return $this->dateRetourPrevue;
    }

    public function setDateRetourPrevue(\DateTimeInterface $dateRetourPrevue): self
    {
        $this->dateRetourPrevue = $dateRetourPrevue;
        return $this;
    }

    public function getDateRetourEffective(): ?\DateTimeInterface
    {
        return $this->dateRetourEffective;
    }

    public function setDateRetourEffective(?\DateTimeInterface $dateRetourEffective): self
    {
        $this->dateRetourEffective = $dateRetourEffective;
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

    // -------- Relations --------

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getLivre(): ?Livre
    {
        return $this->livre;
    }

    public function setLivre(?Livre $livre): self
    {
        $this->livre = $livre;
        return $this;
    }

    public function getBibliothecaire(): ?User
    {
        return $this->bibliothecaire;
    }

    public function setBibliothecaire(?User $bibliothecaire): self
    {
        $this->bibliothecaire = $bibliothecaire;
        return $this;
    }

    // -------- Timestamps --------

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
