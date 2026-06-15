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

    #[ORM\OneToMany(mappedBy: 'dossierMedical', targetEntity: Consultation::class)]
    private Collection $consultations;

    #[ORM\OneToMany(mappedBy: 'dossierMedical', targetEntity: Hospitalisation::class)]
    private Collection $hospitalisations;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $groupeSanguin = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $antecedents = [];

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

    // ✅ LA BONNE VERSION À GARDER
#[ORM\OneToMany(mappedBy: 'dossier', targetEntity: ObservationMedicale::class, cascade: ['persist', 'remove'])]
private Collection $observations;



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

    public function getAntecedents(): ?array
    {
        // Retourne un tableau structuré par défaut pour éviter les erreurs dans les formulaires
        return $this->antecedents ?? [
            'medicaux' => null,
            'chirurgicaux' => null,
            'gyneco_obstetricaux' => null,
            'mode_vie' => null
        ];
    }

    public function setAntecedents(?array $antecedents): self
    {
        $this->antecedents = $antecedents;

        // Si le formulaire enregistre les données en vrac dans le JSON, on les extrait vers les propriétés textes
        if (isset($antecedents['medicaux'])) {
            $this->antecedentsMedicaux = $antecedents['medicaux'];
        }
        if (isset($antecedents['chirurgicaux'])) {
            $this->antecedentsChirurgicaux = $antecedents['chirurgicaux'];
        }

        $this->syncMedicalDataToPatient();
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

    /**
 * @return Collection<int, ObservationMedicale>
 */
public function getObservations(): Collection
{
    return $this->observations;
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


    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function getAntecedentsMedicaux(): ?string
    {
        // On lit la propriété texte, et si elle est vide, on va chercher dans le tableau JSON
        return $this->antecedentsMedicaux ?? $this->antecedents['medicaux'] ?? null;
    }

    public function getAntecedentsChirurgicaux(): ?string
    {
        return $this->antecedentsChirurgicaux ?? $this->antecedents['chirurgicaux'] ?? null;
    }


    public function getMaladiesChroniques(): ?string
    {
        return $this->maladiesChroniques;
    }


    public function getTraitementEnCours(): ?string
    {
        return $this->traitementEnCours;
    }


    public function getHandicap(): ?string
    {
        return $this->handicap;
    }


    public function isGrossesse(): ?bool
    {
        return $this->grossesse;
    }

    public function getGrossesse(): ?bool
    {
        return $this->grossesse;
    }

// --- 1. METHODE DE SYNCHRONISATION SECURISEE ---
    public function syncMedicalDataToPatient(): void
    {
        if ($this->patient === null) {
            return;
        }

        // On vérifie STRICTEMENT (!==) avant de mettre à jour le patient
        if ($this->patient->getGroupeSanguin() !== $this->groupeSanguin) {
            $this->patient->setGroupeSanguin($this->groupeSanguin);
        }
        if ($this->patient->getAllergies() !== $this->allergies) {
            $this->patient->setAllergies($this->allergies);
        }
        // --- NOUVEAU : On utilise les getters sécurisés ---
        $med = $this->getAntecedentsMedicaux();
        if ($this->patient->getAntecedentsMedicaux() !== $med) {
            $this->patient->setAntecedentsMedicaux($med);
        }

        $chir = $this->getAntecedentsChirurgicaux();
        if ($this->patient->getAntecedentsChirurgicaux() !== $chir) {
            $this->patient->setAntecedentsChirurgicaux($chir);
        }
        // --------------------------------------------------
        if ($this->patient->getMaladiesChroniques() !== $this->maladiesChroniques) {
            $this->patient->setMaladiesChroniques($this->maladiesChroniques);
        }
        if ($this->patient->getTraitementEnCours() !== $this->traitementEnCours) {
            $this->patient->setTraitementEnCours($this->traitementEnCours);
        }
        if ($this->patient->getHandicap() !== $this->handicap) {
            $this->patient->setHandicap($this->handicap);
        }
        if ($this->patient->isGrossesse() !== $this->grossesse) {
            $this->patient->setGrossesse($this->grossesse);
        }
    }

    // --- 2. SETTERS QUI DECLENCHENT LA SYNCHRONISATION ---

    public function setGroupeSanguin(?string $groupeSanguin): self
    {
        $this->groupeSanguin = $groupeSanguin;
        $this->syncMedicalDataToPatient(); // 👈 Propagation vers le patient
        return $this;
    }

    public function setAllergies(?string $allergies): self
    {
        $this->allergies = $allergies;
        $this->syncMedicalDataToPatient();
        return $this;
    }

    public function setAntecedentsMedicaux(?string $antecedentsMedicaux): self
    {
        $this->antecedentsMedicaux = $antecedentsMedicaux;
        
        // On met aussi à jour le tableau JSON pour que le formulaire reste cohérent
        $antecedents = $this->getAntecedents();
        $antecedents['medicaux'] = $antecedentsMedicaux;
        $this->antecedents = $antecedents;

        $this->syncMedicalDataToPatient();
        return $this;
    }

    public function setAntecedentsChirurgicaux(?string $antecedentsChirurgicaux): self
    {
        $this->antecedentsChirurgicaux = $antecedentsChirurgicaux;

        $antecedents = $this->getAntecedents();
        $antecedents['chirurgicaux'] = $antecedentsChirurgicaux;
        $this->antecedents = $antecedents;

        $this->syncMedicalDataToPatient();
        return $this;
    }

    public function setMaladiesChroniques(?string $maladiesChroniques): self
    {
        $this->maladiesChroniques = $maladiesChroniques;
        $this->syncMedicalDataToPatient();
        return $this;
    }

    public function setTraitementEnCours(?string $traitementEnCours): self
    {
        $this->traitementEnCours = $traitementEnCours;
        $this->syncMedicalDataToPatient();
        return $this;
    }

    public function setHandicap(?string $handicap): self
    {
        $this->handicap = $handicap;
        $this->syncMedicalDataToPatient();
        return $this;
    }

    public function setGrossesse(?bool $grossesse): self
    {
        $this->grossesse = $grossesse;
        $this->syncMedicalDataToPatient();
        return $this;
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