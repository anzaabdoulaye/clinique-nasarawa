<?php

namespace App\Entity;

use App\Enum\TypePrestationPEC;
use App\Repository\ConventionPriseEnChargeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConventionPriseEnChargeRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_organisme_type_prestation', columns: ['organisme_id', 'type_prestation'])]
#[ORM\HasLifecycleCallbacks]
class ConventionPriseEnCharge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?OrganismePriseEnCharge $organisme = null;

    #[ORM\Column(enumType: TypePrestationPEC::class)]
    private ?TypePrestationPEC $typePrestation = null;

    #[ORM\Column]
    private int $tauxCouverture = 0; // 0, 80, 100

    #[ORM\Column(options: ['default' => true])]
    private bool $actif = true;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganisme(): ?OrganismePriseEnCharge
    {
        return $this->organisme;
    }

    public function setOrganisme(OrganismePriseEnCharge $organisme): static
    {
        $this->organisme = $organisme;
        return $this;
    }

    public function getTypePrestation(): ?TypePrestationPEC
    {
        return $this->typePrestation;
    }

    public function setTypePrestation(TypePrestationPEC $typePrestation): static
    {
        $this->typePrestation = $typePrestation;
        return $this;
    }

    public function getTauxCouverture(): int
    {
        return $this->tauxCouverture;
    }

    public function setTauxCouverture(int $tauxCouverture): static
    {
        if (!in_array($tauxCouverture, [0, 80, 100], true)) {
            throw new \InvalidArgumentException('Le taux doit être 0, 80 ou 100.');
        }

        $this->tauxCouverture = $tauxCouverture;
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

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;
        return $this;
    }

    public function estValideA(\DateTimeInterface $date): bool
    {
        if (!$this->actif) {
            return false;
        }

        if ($this->dateDebut && $date < $this->dateDebut) {
            return false;
        }

        if ($this->dateFin && $date > $this->dateFin) {
            return false;
        }

        return true;
    }
}