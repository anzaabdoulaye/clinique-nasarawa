<?php

namespace App\Entity;

use App\Enum\CategorieTarif;
use App\Repository\TarifPrestationRepository;
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

    #[ORM\Column(options: ['default' => true])]
    private bool $actif = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $serviceExecution = null;

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