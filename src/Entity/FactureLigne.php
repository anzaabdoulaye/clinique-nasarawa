<?php

namespace App\Entity;

use App\Repository\FactureLigneRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\TypePrestationPEC;

#[ORM\Entity(repositoryClass: FactureLigneRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FactureLigne
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Facture $facture = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PrescriptionPrestation $prescriptionPrestation = null;

    #[ORM\Column(length: 180)]
    private string $libelle;

    #[ORM\Column(type: 'integer')]
    private int $quantite = 1;

    #[ORM\Column(type: 'integer')]
    private int $prixUnitaire = 0;

    #[ORM\Column(type: 'integer')]
    private int $total = 0;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $type = null;

    

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $montantBrut = 0;

    #[ORM\Column(enumType: TypePrestationPEC::class, nullable: true)]
    private ?TypePrestationPEC $typePrestationPEC = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $tauxPriseEnCharge = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $montantPriseEnCharge = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $montantPatient = 0;

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function recalculerTotal(): void
    {
        $this->total = max(0, $this->quantite * $this->prixUnitaire);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): static
    {
        $this->facture = $facture;
        return $this;
    }

    public function getPrescriptionPrestation(): ?PrescriptionPrestation
    {
        return $this->prescriptionPrestation;
    }

    public function setPrescriptionPrestation(?PrescriptionPrestation $prescriptionPrestation): static
    {
        $this->prescriptionPrestation = $prescriptionPrestation;
        return $this;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = max(1, $quantite);
        $this->total = $this->quantite * $this->prixUnitaire;
        return $this;
    }

    public function getPrixUnitaire(): int
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(int $prixUnitaire): static
    {
        $this->prixUnitaire = max(0, $prixUnitaire);
        $this->total = $this->quantite * $this->prixUnitaire;
        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = max(0, $total);
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getMontantBrut(): int
    {
        return $this->montantBrut;
    }

    public function setMontantBrut(int $montantBrut): static
    {
        $this->montantBrut = $montantBrut;
        return $this;
    }

    public function getTypePrestationPEC(): ?TypePrestationPEC
    {
        return $this->typePrestationPEC;
    }

    public function setTypePrestationPEC(?TypePrestationPEC $typePrestationPEC): static
    {
        $this->typePrestationPEC = $typePrestationPEC;
        return $this;
    }

    public function getTauxPriseEnCharge(): int
    {
        return $this->tauxPriseEnCharge;
    }

    public function setTauxPriseEnCharge(int $tauxPriseEnCharge): static
    {
        $this->tauxPriseEnCharge = $tauxPriseEnCharge;
        return $this;
    }

    public function getMontantPriseEnCharge(): int
    {
        return $this->montantPriseEnCharge;
    }

    public function setMontantPriseEnCharge(int $montantPriseEnCharge): static
    {
        $this->montantPriseEnCharge = $montantPriseEnCharge;
        return $this;
    }

    public function getMontantPatient(): int
    {
        return $this->montantPatient;
    }

    public function setMontantPatient(int $montantPatient): static
    {
        $this->montantPatient = $montantPatient;
        return $this;
    }
}