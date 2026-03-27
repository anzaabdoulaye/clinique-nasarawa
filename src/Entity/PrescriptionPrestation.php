<?php

namespace App\Entity;

use App\Enum\StatutPrescriptionPrestation;
use App\Repository\PrescriptionPrestationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionPrestationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PrescriptionPrestation
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'prescriptionsPrestations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Consultation $consultation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TarifPrestation $tarifPrestation = null;

    #[ORM\Column(type: 'integer')]
    private int $quantite = 1;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $instructions = null;

    #[ORM\Column(enumType: StatutPrescriptionPrestation::class)]
    private StatutPrescriptionPrestation $statut = StatutPrescriptionPrestation::PRESCRIT;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $aFacturer = true;

    #[ORM\OneToOne(mappedBy: 'prescriptionPrestation', targetEntity: ResultatLaboratoire::class, cascade: ['persist', 'remove'])]
    private ?ResultatLaboratoire $resultatLaboratoire = null;

    public function estVerrouilleePourEdition(): bool
{
    if ($this->getResultatLaboratoire() !== null) {
        return true;
    }

    return in_array($this->getStatut(), [
        StatutPrescriptionPrestation::PRESCRIT,
        StatutPrescriptionPrestation::EN_COURS,
        StatutPrescriptionPrestation::PAYE,
        StatutPrescriptionPrestation::ANNULE,
    ], true);
}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): static
    {
        $this->consultation = $consultation;
        return $this;
    }

    public function getTarifPrestation(): ?TarifPrestation
    {
        return $this->tarifPrestation;
    }

    public function setTarifPrestation(?TarifPrestation $tarifPrestation): static
    {
        $this->tarifPrestation = $tarifPrestation;
        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = max(1, $quantite);
        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): static
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function getStatut(): StatutPrescriptionPrestation
    {
        return $this->statut;
    }

    public function setStatut(StatutPrescriptionPrestation $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function isAFacturer(): bool
    {
        return $this->aFacturer;
    }

    public function setAFacturer(bool $aFacturer): static
    {
        $this->aFacturer = $aFacturer;
        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->tarifPrestation?->getLibelle();
    }

    public function getPrixReference(): int
    {
        return $this->tarifPrestation?->getPrix() ?? 0;
    }

    public function getTotalReference(): int
    {
        return $this->getPrixReference() * $this->quantite;
    }

    public function getCategorieLabel(): ?string
    {
        return $this->tarifPrestation?->getCategorie()?->value;
    }
    public function getResultatLaboratoire(): ?ResultatLaboratoire
    {
        return $this->resultatLaboratoire;
    }

    public function setResultatLaboratoire(?ResultatLaboratoire $resultatLaboratoire): static
    {
        if ($resultatLaboratoire !== null && $resultatLaboratoire->getPrescriptionPrestation() !== $this) {
            $resultatLaboratoire->setPrescriptionPrestation($this);
        }

        $this->resultatLaboratoire = $resultatLaboratoire;
        return $this;
    }
}