<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\HonoraireMedecinRepository;

#[ORM\Entity(repositoryClass: HonoraireMedecinRepository::class)]
#[ORM\HasLifecycleCallbacks]
class HonoraireMedecin
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateActe = null;

    #[ORM\ManyToOne(inversedBy: 'honoraires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Medecin $medecin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomPatient = null;

    #[ORM\Column(length: 255)]
    private ?string $libelleActe = null;

    #[ORM\Column(type: 'float')]
    private ?float $montantTotal = null;

    #[ORM\Column(type: 'float')]
    private ?float $tauxReversement = null; // Exemple : 0.40 pour 40%

    #[ORM\Column(type: 'float')]
    private ?float $montantBrutMedecin = null;

    #[ORM\Column(type: 'float')]
    private ?float $montantIsb = null;

    #[ORM\Column(type: 'float')]
    private ?float $montantNetAPayer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateActe(): ?\DateTime
    {
        return $this->dateActe;
    }

    public function setDateActe(\DateTime $dateActe): static
    {
        $this->dateActe = $dateActe;

        return $this;
    }

    public function getNomPatient(): ?string
    {
        return $this->nomPatient;
    }

    public function setNomPatient(?string $nomPatient): static
    {
        $this->nomPatient = $nomPatient;

        return $this;
    }

    public function getLibelleActe(): ?string
    {
        return $this->libelleActe;
    }

    public function setLibelleActe(string $libelleActe): static
    {
        $this->libelleActe = $libelleActe;

        return $this;
    }

    public function getMontantTotal(): ?float
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(float $montantTotal): static
    {
        $this->montantTotal = $montantTotal;

        return $this;
    }

    public function getTauxReversement(): ?float
    {
        return $this->tauxReversement;
    }

    public function setTauxReversement(float $tauxReversement): static
    {
        $this->tauxReversement = $tauxReversement;

        return $this;
    }

    public function getMontantBrutMedecin(): ?float
    {
        return $this->montantBrutMedecin;
    }

    public function setMontantBrutMedecin(float $montantBrutMedecin): static
    {
        $this->montantBrutMedecin = $montantBrutMedecin;

        return $this;
    }

    public function getMontantIsb(): ?float
    {
        return $this->montantIsb;
    }

    public function setMontantIsb(float $montantIsb): static
    {
        $this->montantIsb = $montantIsb;

        return $this;
    }

    public function getMontantNetAPayer(): ?float
    {
        return $this->montantNetAPayer;
    }

    public function setMontantNetAPayer(float $montantNetAPayer): static
    {
        $this->montantNetAPayer = $montantNetAPayer;

        return $this;
    }

    public function getMedecin(): ?Medecin
    {
        return $this->medecin;
    }

    public function setMedecin(?Medecin $medecin): static
    {
        $this->medecin = $medecin;

        return $this;
    }
}