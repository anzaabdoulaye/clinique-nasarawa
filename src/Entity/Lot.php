<?php

namespace App\Entity;

use App\Repository\LotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LotRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Lot
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lots')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Medicament $medicament;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroLot = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $datePeremption = null;

    #[ORM\Column(type: 'integer')]
    private int $quantite = 0;

    #[ORM\Column(nullable: true)]
    private ?float $prixAchat = null;

    public function getId(): ?int { return $this->id; }

    public function getMedicament(): Medicament { return $this->medicament; }
    public function setMedicament(Medicament $medicament): self { $this->medicament = $medicament; return $this; }

    public function getNumeroLot(): ?string { return $this->numeroLot; }
    public function setNumeroLot(?string $numeroLot): self { $this->numeroLot = $numeroLot; return $this; }

    public function getDatePeremption(): ?\DateTimeInterface { return $this->datePeremption; }
    public function setDatePeremption(?\DateTimeInterface $datePeremption): self { $this->datePeremption = $datePeremption; return $this; }

    public function getQuantite(): int { return $this->quantite; }
    public function setQuantite(int $quantite): self { $this->quantite = $quantite; return $this; }

    public function getPrixAchat(): ?float { return $this->prixAchat; }
    public function setPrixAchat(?float $prixAchat): self { $this->prixAchat = $prixAchat; return $this; }
}
