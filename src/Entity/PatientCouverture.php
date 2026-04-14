<?php

namespace App\Entity;

use App\Repository\PatientCouvertureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PatientCouvertureRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PatientCouverture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'couverturePriseEnCharge')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Patient $patient = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?OrganismePriseEnCharge $organisme = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroAssure = null;

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

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(Patient $patient): static
    {
        $this->patient = $patient;
        return $this;
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

    public function getNumeroAssure(): ?string
    {
        return $this->numeroAssure;
    }

    public function setNumeroAssure(?string $numeroAssure): static
    {
        $this->numeroAssure = $numeroAssure;
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