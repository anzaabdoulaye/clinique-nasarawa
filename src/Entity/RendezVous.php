<?php

namespace App\Entity;

use App\Enum\StatutRendezVous;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RendezVousRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RendezVous
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Patient $patient;

    #[ORM\ManyToOne(inversedBy: 'rendezVous')]
    #[ORM\JoinColumn(nullable: false)]
    private Utilisateur $medecin;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateHeure;

    #[ORM\Column(enumType: StatutRendezVous::class)]
    private StatutRendezVous $statut = StatutRendezVous::PLANIFIE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motif = null;
    
    #[ORM\OneToOne(mappedBy: 'rendezVous', targetEntity: Consultation::class, cascade: ['persist', 'remove'])]
    private ?Consultation $consultation = null;

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): self
    {
        $this->consultation = $consultation;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): Patient
    {
        return $this->patient;
    }

    public function setPatient(Patient $patient): self
    {
        $this->patient = $patient;

        return $this;
    }

    public function getMedecin(): Utilisateur
    {
        return $this->medecin;
    }

    public function setMedecin(Utilisateur $medecin): self
    {
        $this->medecin = $medecin;

        return $this;
    }

    public function getDateHeure(): \DateTimeImmutable
    {
        return $this->dateHeure;
    }

    public function setDateHeure(\DateTimeImmutable $dateHeure): self
    {
        $this->dateHeure = $dateHeure;

        return $this;
    }

    public function getStatut(): StatutRendezVous
    {
        return $this->statut;
    }

    public function setStatut(StatutRendezVous $statut): self
    {
        $this->statut = $statut;

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
