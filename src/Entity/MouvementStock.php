<?php

namespace App\Entity;

use App\Enum\TypeMouvementStock;
use App\Repository\MouvementStockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementStockRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MouvementStock
{
    use TimestampableTrait; // Pour avoir createdAt (la date exacte du mouvement)

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Medicament $medicament = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Lot $lot = null;

    #[ORM\Column(type: 'string', length: 50, enumType: TypeMouvementStock::class)]
    private TypeMouvementStock $type;

    /**
     * Quantité mouvementée (+ pour une entrée, - pour une sortie)
     */
    #[ORM\Column(type: 'integer')]
    private int $quantite;

    /**
     * Le prix d'achat unitaire au moment du mouvement (Crucial pour la compta matière)
     */
    #[ORM\Column(nullable: true)]
    private ?float $valeurAchatUnitaire = null;

    /**
     * Utilisateur ayant enregistré le mouvement
     */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $operateur = null;

    /**
     * Lien de traçabilité : ID d'une consultation, d'un dossier patient ou d'un Bon de Commande
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referenceDocument = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motif = null;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedicament(): ?Medicament
    {
        return $this->medicament;
    }

    public function setMedicament(?Medicament $medicament): self
    {
        $this->medicament = $medicament;

        return $this;
    }

    public function getLot(): ?Lot
    {
        return $this->lot;
    }

    public function setLot(?Lot $lot): self
    {
        $this->lot = $lot;

        return $this;
    }

    public function getType(): TypeMouvementStock
    {
        return $this->type;
    }

    public function setType(TypeMouvementStock $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getValeurAchatUnitaire(): ?float
    {
        return $this->valeurAchatUnitaire;
    }

    public function setValeurAchatUnitaire(?float $valeurAchatUnitaire): self
    {
        $this->valeurAchatUnitaire = $valeurAchatUnitaire;

        return $this;
    }

    public function getOperateur(): ?string
    {
        return $this->operateur;
    }

    public function setOperateur(?string $operateur): self
    {
        $this->operateur = $operateur;

        return $this;
    }

    public function getReferenceDocument(): ?string
    {
        return $this->referenceDocument;
    }

    public function setReferenceDocument(?string $referenceDocument): self
    {
        $this->referenceDocument = $referenceDocument;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): self
    {
        $this->motif = $motif;

        return $this;
    }

}