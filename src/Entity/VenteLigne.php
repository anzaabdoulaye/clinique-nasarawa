<?php

namespace App\Entity;

use App\Repository\VenteLigneRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VenteLigneRepository::class)]
class VenteLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Vente $vente;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Medicament $medicament;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Lot $lot = null;

    #[ORM\Column(type: 'integer')]
    private int $quantite = 1;

    #[ORM\Column]
    private float $prixUnitaire = 0.0;

    public function getId(): ?int { return $this->id; }

    public function getVente(): Vente { return $this->vente; }
    public function setVente(Vente $vente): self { $this->vente = $vente; return $this; }

    public function getMedicament(): Medicament { return $this->medicament; }
    public function setMedicament(Medicament $medicament): self { $this->medicament = $medicament; return $this; }

    public function getLot(): ?Lot { return $this->lot; }
    public function setLot(?Lot $lot): self { $this->lot = $lot; return $this; }

    public function getQuantite(): int { return $this->quantite; }
    public function setQuantite(int $quantite): self { $this->quantite = $quantite; return $this; }

    public function getPrixUnitaire(): float { return $this->prixUnitaire; }
    public function setPrixUnitaire(float $prixUnitaire): self { $this->prixUnitaire = $prixUnitaire; return $this; }

    public function getSousTotal(): float { return $this->prixUnitaire * $this->quantite; }
}
