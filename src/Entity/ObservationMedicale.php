<?php

namespace App\Entity;

use App\Repository\ObservationMedicaleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ObservationMedicaleRepository::class)]
#[Gedmo\Loggable] // Indique à Gedmo que cette entité doit être auditée
class ObservationMedicale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'observations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DossierMedical $dossier = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ? Utilisateur $medecinAuteur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Hospitalisation $hospitalisation = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Gedmo\Versioned] // Ce champ sera tracé en cas de modification par l'admin
    private ?string $contenu = null;

    #[ORM\Column(length: 255)]
    #[Gedmo\Versioned] // Ce champ sera également tracé en cas de modification
    private ?string $typeObservation = null; // Ex: Diagnostic, Évolution, Prescription, etc.

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        // Initialisation automatique à la date et heure locale
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('Africa/Niamey'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDossier(): ?DossierMedical
    {
        return $this->dossier;
    }

    public function setDossier(?DossierMedical $dossier): static
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function getMedecinAuteur(): ? Utilisateur
    {
        return $this->medecinAuteur;
    }

    public function setMedecinAuteur(? Utilisateur $medecinAuteur): static
    {
        $this->medecinAuteur = $medecinAuteur;

        return $this;
    }

    public function getHospitalisation(): ?Hospitalisation
    {
        return $this->hospitalisation;
    }

    public function setHospitalisation(?Hospitalisation $hospitalisation): static
    {
        $this->hospitalisation = $hospitalisation;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getTypeObservation(): ?string
    {
        return $this->typeObservation;
    }

    public function setTypeObservation(string $typeObservation): static
    {
        $this->typeObservation = $typeObservation;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}