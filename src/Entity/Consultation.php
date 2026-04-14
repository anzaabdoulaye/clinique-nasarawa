<?php

namespace App\Entity;

use App\Enum\StatutConsultation;
use App\Repository\ConsultationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Consultation
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $medecin = null;

    #[ORM\ManyToOne(inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DossierMedical $dossierMedical = null;

     #[ORM\OneToOne(inversedBy: 'consultation')]
    #[ORM\JoinColumn(nullable: true, unique: true)]
    private ?RendezVous $rendezVous = null;

    #[ORM\Column(enumType: StatutConsultation::class)]
    private StatutConsultation $statut = StatutConsultation::BROUILLON;

    #[ORM\Column(nullable: true)]
    private ?float $poids = null;

    #[ORM\Column(nullable: true)]
    private ?float $taille = null;

    #[ORM\Column(nullable: true)]
    private ?float $temperature = null;

    #[ORM\Column(nullable: true)]
    private ?string $tensionArterielle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motifs = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $diagnostic = null;

    #[ORM\Column(nullable: true)]
    private ?int $frequenceCardiaque = null;

    #[ORM\OneToMany(mappedBy: 'consultation', targetEntity: Prescription::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $prescriptions;

    #[ORM\OneToOne(mappedBy: 'consultation', targetEntity: Facture::class, cascade: ['persist', 'remove'])]
    private ?Facture $facture = null;


    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $histoire = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $examenClinique = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $conduiteATenir = null;

    // Optionnel : CIM10
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Cim10Code $cim10 = null;

    #[ORM\OneToMany(mappedBy: 'consultation', targetEntity: PrescriptionPrestation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
  private Collection $prescriptionsPrestations;

  #[ORM\Column(type: 'datetime_immutable', nullable: true)]
private ?\DateTimeImmutable $dateCloture = null;

    public function getHistoire(): ?string { return $this->histoire; }
    public function setHistoire(?string $histoire): self { $this->histoire = $histoire; return $this; }

    public function getExamenClinique(): ?string { return $this->examenClinique; }
    public function setExamenClinique(?string $examenClinique): self { $this->examenClinique = $examenClinique; return $this; }

    public function getConduiteATenir(): ?string { return $this->conduiteATenir; }
    public function setConduiteATenir(?string $conduiteATenir): self { $this->conduiteATenir = $conduiteATenir; return $this; }

    public function getCim10(): ?Cim10Code { return $this->cim10; }
    public function setCim10(?Cim10Code $cim10): self { $this->cim10 = $cim10; return $this; }


    public function __construct()
    {
        $this->prescriptions = new ArrayCollection();
        $this->prescriptionsPrestations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedecin(): ?Utilisateur
    {
        return $this->medecin;
    }

    public function setMedecin(Utilisateur $medecin): self
    {
        $this->medecin = $medecin;

        return $this;
    }

    public function getDossierMedical(): ?DossierMedical
    {
        return $this->dossierMedical;
    }

    public function setDossierMedical(?DossierMedical $dossierMedical): self
    {
        $this->dossierMedical = $dossierMedical;

        return $this;
    }

    public function getPoids(): ?float
    {
        return $this->poids;
    }

    public function setPoids(?float $poids): self
    {
        $this->poids = $poids;

        return $this;
    }

    public function getTaille(): ?float
    {
        return $this->taille;
    }

    public function setTaille(?float $taille): self
    {
        $this->taille = $taille;

        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getTensionArterielle(): ?string
    {
        return $this->tensionArterielle;
    }

    public function setTensionArterielle(?string $tensionArterielle): self
    {
        $this->tensionArterielle = $tensionArterielle;

        return $this;
    }

    public function getMotifs(): ?string
    {
        return $this->motifs;
    }

    public function setMotifs(?string $motifs): self
    {
        $this->motifs = $motifs;

        return $this;
    }

    public function getDiagnostic(): ?string
    {
        return $this->diagnostic;
    }

    public function setDiagnostic(?string $diagnostic): self
    {
        $this->diagnostic = $diagnostic;

        return $this;
    }

    public function getFrequenceCardiaque(): ?int
    {
        return $this->frequenceCardiaque;
    }

    public function setFrequenceCardiaque(?int $frequenceCardiaque): self
    {
        $this->frequenceCardiaque = $frequenceCardiaque;

        return $this;
    }

    /**
     * @return Collection<int, Prescription>
     */
    public function getPrescriptions(): Collection
    {
        return $this->prescriptions;
    }

    public function addPrescription(Prescription $prescription): self
    {
        if (!$this->prescriptions->contains($prescription)) {
            $this->prescriptions->add($prescription);
            $prescription->setConsultation($this);
        }

        return $this;
    }

    public function removePrescription(Prescription $prescription): self
    {
        $this->prescriptions->removeElement($prescription);

        return $this;
    }
    public function getPrescriptionsPrestations(): Collection
    {
        return $this->prescriptionsPrestations;
    }

    public function addPrescriptionPrestation(PrescriptionPrestation $prescriptionPrestation): static
    {
        if (!$this->prescriptionsPrestations->contains($prescriptionPrestation)) {
            $this->prescriptionsPrestations->add($prescriptionPrestation);
            $prescriptionPrestation->setConsultation($this);
        }

        return $this;
    }

    public function removePrescriptionPrestation(PrescriptionPrestation $prescriptionPrestation): static
    {
        if ($this->prescriptionsPrestations->removeElement($prescriptionPrestation)) {
            if ($prescriptionPrestation->getConsultation() === $this) {
                $prescriptionPrestation->setConsultation(null);
            }
        }

        return $this;
    }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): static
    {
        if ($facture !== null && $facture->getConsultation() !== $this) {
            $facture->setConsultation($this);
        }

        $this->facture = $facture;
        return $this;
    }


    public function getRendezVous(): ?RendezVous
    {
        return $this->rendezVous;
    }

    public function setRendezVous(?RendezVous $rendezVous): self
    {
        if ($this->rendezVous !== null && $this->rendezVous !== $rendezVous && $this->rendezVous->getConsultation() === $this) {
            $this->rendezVous->setConsultation(null);
        }

        $this->rendezVous = $rendezVous;

        if ($rendezVous !== null && $rendezVous->getConsultation() !== $this) {
            $rendezVous->setConsultation($this);
        }

        return $this;
    }

    public function getStatut(): StatutConsultation
    {
        return $this->statut;
    }

    public function setStatut(StatutConsultation $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getNumeroFiche(): string
    {
        return sprintf(
            'FC-%s-%04d',
            $this->getCreatedAt()?->format('Ymd') ?? date('Ymd'),
            $this->getId() ?? 0
        );
    }

    public function getDateCloture(): ?\DateTimeImmutable
{
    return $this->dateCloture;
}

public function setDateCloture(?\DateTimeImmutable $dateCloture): self
{
    $this->dateCloture = $dateCloture;
    return $this;
}

public function estCloturee(): bool
{
    return $this->statut === StatutConsultation::CLOTURE;
}

public function estAnnulee(): bool
{
    return $this->statut === StatutConsultation::ANNULE;
}

public function estModifiable(): bool
{
    return !$this->estCloturee() && !$this->estAnnulee();
}
}