<?php

namespace App\Entity;

use App\Enum\StatutHospitalisation;
use App\Repository\HospitalisationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HospitalisationRepository::class)]
class Hospitalisation
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'hospitalisations')]
    #[ORM\JoinColumn(nullable: false)]
    private DossierMedical $dossierMedical;

    #[ORM\ManyToOne(inversedBy: 'hospitalisations')]
    #[ORM\JoinColumn(nullable: false)]
    private Utilisateur $medecinReferent;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateAdmission;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateSortie = null;

    #[ORM\Column(length: 255)]
    private string $motifAdmission;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $histoireMaladie = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $evolution = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $conclusion = null;

    #[ORM\Column(enumType: StatutHospitalisation::class)]
    private StatutHospitalisation $statut = StatutHospitalisation::EN_COURS;

    #[ORM\OneToOne(mappedBy: 'hospitalisation', targetEntity: ExamenClinique::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?ExamenClinique $examenClinique = null;

    #[ORM\OneToOne(mappedBy: 'hospitalisation', targetEntity: ExamenNeurologique::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?ExamenNeurologique $examenNeurologique = null;

    #[ORM\OneToMany(mappedBy: 'hospitalisation', targetEntity: ExamenComplementaire::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $examensComplementaires;

    #[ORM\OneToMany(mappedBy: 'hospitalisation', targetEntity: Antecedent::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $antecedents;

    #[ORM\OneToMany(mappedBy: 'hospitalisation', targetEntity: TraitementHospitalisation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $traitements;

    public function __construct(
        DossierMedical $dossierMedical,
        Utilisateur $medecinReferent,
        string $motifAdmission
    ) {
        $this->dossierMedical = $dossierMedical;
        $this->medecinReferent = $medecinReferent;
        $this->motifAdmission = $motifAdmission;
        $this->dateAdmission = new \DateTimeImmutable();
        $this->statut = StatutHospitalisation::EN_COURS;
        $this->traitements = new ArrayCollection();
        $this->examensComplementaires = new ArrayCollection();
        $this->antecedents = new ArrayCollection();
    }

    // --------------------
    // GETTERS
    // --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDossierMedical(): DossierMedical
    {
        return $this->dossierMedical;
    }

    public function getMedecinReferent(): Utilisateur
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

    /**
     * @return Collection<int, TraitementHospitalisation>
     */
    public function getTraitements(): Collection
    {
        return $this->traitements;
    }

    // --------------------
    // SETTERS contrôlés
    // --------------------

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

    public function setDossierMedical(DossierMedical $dossierMedical): self
    {
        $this->dossierMedical = $dossierMedical;

        return $this;
    }

    public function setMedecinReferent(Utilisateur $medecinReferent): self
    {
        $this->medecinReferent = $medecinReferent;

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

    public function setStatut(StatutHospitalisation $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getExamenClinique(): ?ExamenClinique
    {
        return $this->examenClinique;
    }

    public function setExamenClinique(?ExamenClinique $examenClinique): self
    {
        $this->examenClinique = $examenClinique;

        if ($examenClinique !== null && $examenClinique->getHospitalisation() !== $this) {
            $examenClinique->setHospitalisation($this);
        }

        return $this;
    }

    public function getExamenNeurologique(): ?ExamenNeurologique
    {
        return $this->examenNeurologique;
    }

    public function setExamenNeurologique(?ExamenNeurologique $examenNeurologique): self
    {
        $this->examenNeurologique = $examenNeurologique;

        if ($examenNeurologique !== null && $examenNeurologique->getHospitalisation() !== $this) {
            $examenNeurologique->setHospitalisation($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ExamenComplementaire>
     */
    public function getExamensComplementaires(): Collection
    {
        return $this->examensComplementaires;
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

    /**
     * @return Collection<int, Antecedent>
     */
    public function getAntecedents(): Collection
    {
        return $this->antecedents;
    }

    public function addAntecedent(Antecedent $antecedent): self
    {
        if (!$this->antecedents->contains($antecedent)) {
            $this->antecedents->add($antecedent);
            $antecedent->setHospitalisation($this);
        }

        return $this;
    }

    public function removeAntecedent(Antecedent $antecedent): self
    {
        if ($this->antecedents->removeElement($antecedent)) {
            if ($antecedent->getHospitalisation() === $this) {
                $antecedent->setHospitalisation(null);
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
}
