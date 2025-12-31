<?php

namespace App\Entity;

use App\Repository\LivrePdfRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: LivrePdfRepository::class)]
class LivrePdf
{
    // ----------------- Champs -----------------
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $fichier;

    #[ORM\OneToOne(inversedBy: 'pdf', targetEntity: Livre::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livre $livre = null;

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

    public function getFichier(): string
    {
        return $this->fichier;
    }

    public function setFichier(string $fichier): self
    {
        $this->fichier = $fichier;
        return $this;
    }

    public function getLivre(): ?Livre
    {
        return $this->livre;
    }

    public function setLivre(?Livre $livre): self
    {
        $this->livre = $livre;

        // Maintien de la relation bidirectionnelle
        if ($livre && $livre->getPdf() !== $this) {
            $livre->setPdf($this);
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
}
