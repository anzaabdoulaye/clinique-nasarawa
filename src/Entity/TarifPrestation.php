<?php

namespace App\Entity;

use App\Enum\CategorieTarif;
use App\Repository\TarifPrestationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TarifPrestationRepository::class)]
#[ORM\Table(name: 'tarif_prestation')]
class TarifPrestation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $libelle;

    #[ORM\Column(length: 180, unique: true)]
    private string $code;

    #[ORM\Column(enumType: CategorieTarif::class)]
    private CategorieTarif $categorie;

    #[ORM\Column(type: 'integer')]
    private int $prix = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $prixPriseEnCharge = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $actif = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $serviceExecution = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Medicament::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?\App\Entity\Medicament $medicament = null;

    // ... (ajoutez les getters et setters correspondants)
    public function getMedicament(): ?\App\Entity\Medicament
    {
        return $this->medicament;
    }

    public function setMedicament(?\App\Entity\Medicament $medicament): static
    {
        $this->medicament = $medicament;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getCategorie(): CategorieTarif
    {
        return $this->categorie;
    }

    public function setCategorie(CategorieTarif $categorie): static
    {
        $this->categorie = $categorie;

        // On vérifie si la catégorie fait partie des examens gérés par le labo
        if ($categorie === CategorieTarif::EXAMEN_BIOLOGIQUE || $categorie === CategorieTarif::EXAMEN_FONCTIONNEL) {
            $this->serviceExecution = 'laboratoire';
        } 
        // Si on change pour une catégorie qui n'est plus du ressort du labo, on réinitialise
        elseif ($this->serviceExecution === 'laboratoire') {
            $this->serviceExecution = null;
        }

        return $this;
    }
    public function getPrix(): int
    {
        return $this->prix;
    }

    public function setPrix(int $prix): static
    {
        $this->prix = max(0, $prix);
        return $this;
    }

    public function getPrixPriseEnCharge(): ?int
    {
        return $this->prixPriseEnCharge;
    }

    public function setPrixPriseEnCharge(?int $prixPriseEnCharge): self
    {
        $this->prixPriseEnCharge = $prixPriseEnCharge;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getServiceExecution(): ?string
    {
        return $this->serviceExecution;
    }

    public function setServiceExecution(?string $serviceExecution): static
    {
        $this->serviceExecution = $serviceExecution;
        return $this;
    }
}