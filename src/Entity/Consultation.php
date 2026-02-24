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
    private Utilisateur $medecin;

    #[ORM\ManyToOne(inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false)]
    private DossierMedical $dossierMedical;

     #[ORM\OneToOne(inversedBy: 'consultation')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?RendezVous $rendezVous = null;

    #[ORM\Column(enumType: StatutConsultation::class)]
    private StatutConsultation $statut = StatutConsultation::BROUILLON;

    public function getRendezVous(): ?RendezVous
    {
        return $this->rendezVous;
    }

    public function setRendezVous(RendezVous $rendezVous): self
    {
        $this->rendezVous = $rendezVous;

        if ($rendezVous->getConsultation() !== $this) {
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

    #[ORM\OneToOne(mappedBy: 'consultation', targetEntity: Facture::class)]
    private ?Facture $facture = null;

    public function __construct()
    {
        $this->prescriptions = new ArrayCollection();
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

    public function setDossierMedical(DossierMedical $dossierMedical): self
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
        if ($this->prescriptions->removeElement($prescription)) {
            if ($prescription->getConsultation() === $this) {
                $prescription->setConsultation(null);
            }
        }

        return $this;
    }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): self
    {
        $this->facture = $facture;

        if ($facture !== null && $facture->getConsultation() !== $this) {
            $facture->setConsultation($this);
        }

        return $this;
    }
}