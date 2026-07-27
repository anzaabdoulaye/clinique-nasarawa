<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\JournalCaisseRepository;

#[ORM\Entity(repositoryClass: JournalCaisseRepository::class)]
#[ORM\HasLifecycleCallbacks]
class JournalCaisse
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateOperation = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'float')]
    private ?float $montant = null;

    #[ORM\Column(length: 50)]
    private ?string $typeOperation = null; // "ENTREE" ou "SORTIE"

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categorie = null; // "Frais généraux", "Salaire", "Fournitures"

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pieceJustificative = null; // Numéro de facture, reçu, etc.

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateOperation(): ?\DateTime
    {
        return $this->dateOperation;
    }

    public function setDateOperation(\DateTime $dateOperation): static
    {
        $this->dateOperation = $dateOperation;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getTypeOperation(): ?string
    {
        return $this->typeOperation;
    }

    public function setTypeOperation(string $typeOperation): static
    {
        $this->typeOperation = $typeOperation;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getPieceJustificative(): ?string
    {
        return $this->pieceJustificative;
    }

    public function setPieceJustificative(?string $pieceJustificative): static
    {
        $this->pieceJustificative = $pieceJustificative;

        return $this;
    }
}