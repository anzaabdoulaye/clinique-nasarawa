<?php

namespace App\Entity;

use App\Repository\DossierMedicalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierMedicalRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DossierMedical
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'dossierMedical')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Patient $patient = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $numeroDossier;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observations = null;

    #[ORM\OneToMany(mappedBy: 'dossierMedical', targetEntity: Consultation::class)]
    private Collection $consultations;

    #[ORM\OneToMany(mappedBy: 'dossierMedical', targetEntity: Hospitalisation::class)]
    private Collection $hospitalisations;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $groupeSanguin = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $allergies = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $antecedentsMedicaux = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $antecedentsChirurgicaux = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $maladiesChroniques = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $traitementEnCours = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $handicap = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $grossesse = null;

    public function __construct()
    {
        $this->consultations = new ArrayCollection();
        $this->hospitalisations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(Patient $patient): self
    {
        $this->patient = $patient;

        return $this;
    }

    public function getNumeroDossier(): string
    {
        return $this->numeroDossier;
    }

    public function setNumeroDossier(string $numeroDossier): self
    {
        $this->numeroDossier = $numeroDossier;

        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): self
    {
        $this->observations = $observations;

        return $this;
    }

    /**
     * @return Collection<int, Consultation>
     */
    public function getConsultations(): Collection
    {
        return $this->consultations;
    }

    public function addConsultation(Consultation $consultation): self
    {
        if (!$this->consultations->contains($consultation)) {
            $this->consultations->add($consultation);
            $consultation->setDossierMedical($this);
        }

        return $this;
    }

    public function removeConsultation(Consultation $consultation): self
    {
        if ($this->consultations->removeElement($consultation)) {
            if ($consultation->getDossierMedical() === $this) {
                $consultation->setDossierMedical(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Hospitalisation>
     */
    public function getHospitalisations(): Collection
    {
        return $this->hospitalisations;
    }

    public function addHospitalisation(Hospitalisation $hospitalisation): self
    {
        if (!$this->hospitalisations->contains($hospitalisation)) {
            $this->hospitalisations->add($hospitalisation);
            $hospitalisation->setDossierMedical($this);
        }

        return $this;
    }

    public function removeHospitalisation(Hospitalisation $hospitalisation): self
    {
        if ($this->hospitalisations->removeElement($hospitalisation)) {
            if ($hospitalisation->getDossierMedical() === $this) {
                $hospitalisation->setDossierMedical(null);
            }
        }

        return $this;
    }

    public function getGroupeSanguin(): ?string
    {
        return $this->groupeSanguin;
    }

    public function setGroupeSanguin(?string $groupeSanguin): self
    {
        $this->groupeSanguin = $groupeSanguin;

        return $this;
    }

    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function setAllergies(?string $allergies): self
    {
        $this->allergies = $allergies;

        return $this;
    }

    public function getAntecedentsMedicaux(): ?string
    {
        return $this->antecedentsMedicaux;
    }

    public function setAntecedentsMedicaux(?string $antecedentsMedicaux): self
    {
        $this->antecedentsMedicaux = $antecedentsMedicaux;

        return $this;
    }

    public function getAntecedentsChirurgicaux(): ?string
    {
        return $this->antecedentsChirurgicaux;
    }

    public function setAntecedentsChirurgicaux(?string $antecedentsChirurgicaux): self
    {
        $this->antecedentsChirurgicaux = $antecedentsChirurgicaux;

        return $this;
    }

    public function getMaladiesChroniques(): ?string
    {
        return $this->maladiesChroniques;
    }

    public function setMaladiesChroniques(?string $maladiesChroniques): self
    {
        $this->maladiesChroniques = $maladiesChroniques;

        return $this;
    }

    public function getTraitementEnCours(): ?string
    {
        return $this->traitementEnCours;
    }

    public function setTraitementEnCours(?string $traitementEnCours): self
    {
        $this->traitementEnCours = $traitementEnCours;

        return $this;
    }

    public function getHandicap(): ?string
    {
        return $this->handicap;
    }

    public function setHandicap(?string $handicap): self
    {
        $this->handicap = $handicap;

        return $this;
    }

    public function isGrossesse(): ?bool
    {
        return $this->grossesse;
    }

    public function getGrossesse(): ?bool
    {
        return $this->grossesse;
    }

    public function setGrossesse(?bool $grossesse): self
    {
        $this->grossesse = $grossesse;

        return $this;
    }

    public function syncMedicalDataToPatient(): void
    {
        if ($this->patient === null) {
            return;
        }

        $this->patient->setGroupeSanguin($this->groupeSanguin);
        $this->patient->setAllergies($this->allergies);
        $this->patient->setAntecedentsMedicaux($this->antecedentsMedicaux);
        $this->patient->setAntecedentsChirurgicaux($this->antecedentsChirurgicaux);
        $this->patient->setMaladiesChroniques($this->maladiesChroniques);
        $this->patient->setTraitementEnCours($this->traitementEnCours);
        $this->patient->setHandicap($this->handicap);
        $this->patient->setGrossesse($this->grossesse);
    }

    public function getNombreConsultations(): int
    {
        return $this->consultations->count();
    }

    public function getNombreHospitalisations(): int
    {
        return $this->hospitalisations->count();
    }
    
}