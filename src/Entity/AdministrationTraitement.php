<?php

namespace App\Entity;

use App\Repository\AdministrationTraitementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdministrationTraitementRepository::class)]
#[ORM\Table(name: 'administration_traitement')]
#[ORM\UniqueConstraint(name: 'uniq_traitement_date_heure', columns: ['traitement_id', 'date_administration', 'heure'])]
class AdministrationTraitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'administrations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TraitementHospitalisation $traitement = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $dateAdministration = null;

    #[ORM\Column(type: 'smallint')]
    private ?int $heure = null;

    #[ORM\Column(length: 20)]
    private string $statut = 'administre';

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $administreLe = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTraitement(): ?TraitementHospitalisation
    {
        return $this->traitement;
    }

    public function setTraitement(?TraitementHospitalisation $traitement): static
    {
        $this->traitement = $traitement;
        return $this;
    }

    public function getDateAdministration(): ?\DateTimeImmutable
    {
        return $this->dateAdministration;
    }

    public function setDateAdministration(?\DateTimeImmutable $dateAdministration): static
    {
        $this->dateAdministration = $dateAdministration;
        return $this;
    }

    public function getHeure(): ?int
    {
        return $this->heure;
    }

    public function setHeure(?int $heure): static
    {
        $this->heure = $heure;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getAdministreLe(): ?\DateTimeImmutable
    {
        return $this->administreLe;
    }

    public function setAdministreLe(?\DateTimeImmutable $administreLe): static
    {
        $this->administreLe = $administreLe;
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
}