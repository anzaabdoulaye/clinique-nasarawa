<?php

namespace App\Entity;

use App\Enum\StatutHospitalisation;
use App\Repository\HospitalisationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HospitalisationRepository::class)]
#[ORM\HasLifecycleCallbacks] 
class Hospitalisation
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ Nullable en PHP pour permettre new Hospitalisation() + form binding
    #[ORM\ManyToOne(inversedBy: 'hospitalisations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DossierMedical $dossierMedical = null;

    // ✅ Nullable en PHP pour permettre new Hospitalisation() + form binding
    #[ORM\ManyToOne(inversedBy: 'hospitalisations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $medecinReferent = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateAdmission;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateSortie = null;

    #[ORM\Column(length: 255)]
    private string $motifAdmission = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $histoireMaladie = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $evolution = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $conclusion = null;

    #[ORM\Column(enumType: StatutHospitalisation::class)]
    private StatutHospitalisation $statut = StatutHospitalisation::EN_COURS;

    // ✅ NOUVEAU CODE (OneToMany pour les constantes)
    #[ORM\OneToMany(mappedBy: 'hospitalisation', targetEntity: ExamenClinique::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $examensCliniques;

    // ✅ NOUVEAUX CHAMPS (Diagnostics et Bilans)
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $bilanParaclinique = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $hypothesesDiagnostiques = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $diagnosticPositif = null;

    

    #[ORM\OneToOne(mappedBy: 'hospitalisation', targetEntity: ExamenNeurologique::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?ExamenNeurologique $examenNeurologique = null;

    #[ORM\OneToMany(mappedBy: 'hospitalisation', targetEntity: ExamenComplementaire::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $examensComplementaires;

    #[ORM\OneToMany(mappedBy: 'hospitalisation', targetEntity: TraitementHospitalisation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $traitements;

   public function __construct()
    {
        $this->examensComplementaires = new ArrayCollection();
        $this->antecedents = new ArrayCollection();
        $this->traitements = new ArrayCollection();
        // ✅ Initialiser la nouvelle collection
        $this->examensCliniques = new ArrayCollection(); 
        $this->dateAdmission = new \DateTimeImmutable();
    }

    // --------------------
    // GETTERS
    // --------------------

    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDossierMedical(): ?DossierMedical
    {
        return $this->dossierMedical;
    }

    public function getMedecinReferent(): ?Utilisateur
    {
        return $this->medecinReferent;
    }

    public function getDateAdmission(): \DateTimeImmutable
    {
        return $this->dateAdmission;
    }

    public function getDateSortie(): ?\DateTimeImmutable
    {
        return $this->dateSortie;
    }

    public function getMotifAdmission(): string
    {
        return $this->motifAdmission;
    }

    public function getHistoireMaladie(): ?string
    {
        return $this->histoireMaladie;
    }

    public function getEvolution(): ?string
    {
        return $this->evolution;
    }

    public function getConclusion(): ?string
    {
        return $this->conclusion;
    }

    public function getStatut(): StatutHospitalisation
    {
        return $this->statut;
    }

    public function getExamenClinique(): ?ExamenClinique
    {
        return $this->examenClinique;
    }

    public function getExamenNeurologique(): ?ExamenNeurologique
    {
        return $this->examenNeurologique;
    }

    /**
     * @return Collection<int, ExamenComplementaire>
     */
    public function getExamensComplementaires(): Collection
    {
        return $this->examensComplementaires;
    }

    /**
     * @return Collection<int, Antecedent>
     */
    public function getAntecedents(): Collection
    {
        return $this->antecedents;
    }

    /**
     * @return Collection<int, TraitementHospitalisation>
     */
    public function getTraitements(): Collection
    {
        return $this->traitements;
    }

    // --------------------
    // SETTERS
    // --------------------

    public function setDossierMedical(?DossierMedical $dossierMedical): self
    {
        $this->dossierMedical = $dossierMedical;
        return $this;
    }

    public function setMedecinReferent(?Utilisateur $medecinReferent): self
    {
        $this->medecinReferent = $medecinReferent;
        return $this;
    }

    public function setDateAdmission(\DateTimeImmutable $dateAdmission): self
    {
        $this->dateAdmission = $dateAdmission;
        return $this;
    }

    public function setDateSortie(?\DateTimeImmutable $dateSortie): self
    {
        $this->dateSortie = $dateSortie;
        return $this;
    }

    public function setMotifAdmission(string $motifAdmission): self
    {
        $this->motifAdmission = $motifAdmission;
        return $this;
    }

    public function setHistoireMaladie(?string $histoireMaladie): self
    {
        $this->histoireMaladie = $histoireMaladie;
        return $this;
    }

    public function setEvolution(?string $evolution): self
    {
        $this->evolution = $evolution;
        return $this;
    }

    public function setConclusion(?string $conclusion): self
    {
        $this->conclusion = $conclusion;
        return $this;
    }

    public function setStatut(StatutHospitalisation $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function setExamenClinique(?ExamenClinique $examenClinique): self
    {
        $this->examenClinique = $examenClinique;

        if ($examenClinique !== null && $examenClinique->getHospitalisation() !== $this) {
            $examenClinique->setHospitalisation($this);
        }

        return $this;
    }

    public function setExamenNeurologique(?ExamenNeurologique $examenNeurologique): self
    {
        $this->examenNeurologique = $examenNeurologique;

        if ($examenNeurologique !== null && $examenNeurologique->getHospitalisation() !== $this) {
            $examenNeurologique->setHospitalisation($this);
        }

        return $this;
    }

    public function addExamenComplementaire(ExamenComplementaire $examen): self
    {
        if (!$this->examensComplementaires->contains($examen)) {
            $this->examensComplementaires->add($examen);
            $examen->setHospitalisation($this);
        }
        return $this;
    }

    public function removeExamenComplementaire(ExamenComplementaire $examen): self
    {
        if ($this->examensComplementaires->removeElement($examen)) {
            if ($examen->getHospitalisation() === $this) {
                $examen->setHospitalisation(null);
            }
        }
        return $this;
    }

    public function addTraitement(TraitementHospitalisation $traitement): self
    {
        if (!$this->traitements->contains($traitement)) {
            $this->traitements->add($traitement);
            $traitement->setHospitalisation($this);
        }
        return $this;
    }

    public function removeTraitement(TraitementHospitalisation $traitement): self
    {
        if ($this->traitements->removeElement($traitement)) {
            if ($traitement->getHospitalisation() === $this) {
                $traitement->setHospitalisation(null);
            }
        }
        return $this;
    }

    // (Optionnel) Factory métier si tu veux imposer des invariants
    public static function creer(DossierMedical $dossier, Utilisateur $medecin, string $motif): self
    {
        $self = new self();
        $self->dossierMedical = $dossier;
        $self->medecinReferent = $medecin;
        $self->motifAdmission = $motif;

        return $self;
    }

    // ✅ NOUVELLES METHODES pour les examens cliniques (constantes)
    /**
     * @return Collection<int, ExamenClinique>
     */
    public function getExamensCliniques(): Collection
    {
        return $this->examensCliniques;
    }

    public function addExamenClinique(ExamenClinique $examenClinique): self
    {
        if (!$this->examensCliniques->contains($examenClinique)) {
            $this->examensCliniques->add($examenClinique);
            $examenClinique->setHospitalisation($this);
        }
        return $this;
    }

    public function removeExamenClinique(ExamenClinique $examenClinique): self
    {
        if ($this->examensCliniques->removeElement($examenClinique)) {
            if ($examenClinique->getHospitalisation() === $this) {
                $examenClinique->setHospitalisation(null);
            }
        }
        return $this;
    }

    // ✅ NOUVELLES METHODES pour les bilans et diagnostics
    public function getBilanParaclinique(): ?array
    {
        // Retourne une structure par défaut si vide, très pratique pour les formulaires Twig
        return $this->bilanParaclinique ?? [
            'imagerie' => null,
            'biologie' => null,
        ];
    }

    public function setBilanParaclinique(?array $bilanParaclinique): self
    {
        $this->bilanParaclinique = $bilanParaclinique;
        return $this;
    }

    public function getHypothesesDiagnostiques(): ?string
    {
        return $this->hypothesesDiagnostiques;
    }

    public function setHypothesesDiagnostiques(?string $hypothesesDiagnostiques): self
    {
        $this->hypothesesDiagnostiques = $hypothesesDiagnostiques;
        return $this;
    }

    public function getDiagnosticPositif(): ?string
    {
        return $this->diagnosticPositif;
    }

    public function setDiagnosticPositif(?string $diagnosticPositif): self
    {
        $this->diagnosticPositif = $diagnosticPositif;
        return $this;
    }
}