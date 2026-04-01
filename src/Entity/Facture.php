<?php

namespace App\Entity;

use App\Enum\StatutFacture;
use App\Repository\FactureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FactureRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Facture
{
    use TimestampableTrait;

    private const SEUIL_TIMBRE = 5000;
    private const MONTANT_TIMBRE = 200;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'facture')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Consultation $consultation = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $montantTotal = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $montantPaye = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $resteAPayer = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateEmission;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $datePaiement = null;

    #[ORM\Column(enumType: StatutFacture::class)]
    private StatutFacture $statut = StatutFacture::BROUILLON;

    #[ORM\OneToMany(mappedBy: 'facture', targetEntity: FactureLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    #[ORM\OneToMany(mappedBy: 'facture', targetEntity: Paiement::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $paiements;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->paiements = new ArrayCollection();
        $this->dateEmission = new \DateTimeImmutable();
        $this->statut = StatutFacture::BROUILLON;
        $this->montantTotal = 0;
        $this->montantPaye = 0;
        $this->resteAPayer = 0;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (!isset($this->dateEmission)) {
            $this->dateEmission = new \DateTimeImmutable();
        }
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

    public function getMontantTotal(): int
    {
        return $this->montantTotal ?? 0;
    }

    public function setMontantTotal(int $montantTotal): static
    {
        $this->montantTotal = max(0, $montantTotal);
        return $this;
    }

    public function getBaseTotal(): int
    {
        if (!$this->lignes->isEmpty()) {
            $montantBase = 0;

            foreach ($this->lignes as $ligne) {
                $montantBase += $ligne->getTotal();
            }

            return $montantBase;
        }

        $montantTotal = $this->getMontantTotal();

        if ($montantTotal >= self::SEUIL_TIMBRE + self::MONTANT_TIMBRE + 1) {
            return max(0, $montantTotal - self::MONTANT_TIMBRE);
        }

        return $montantTotal;
    }

    public function getTimbreAmount(): int
    {
        return $this->computeTimbreAmount($this->getBaseTotal());
    }

    public function calculerMontantAvecTimbre(int $montantBase): int
    {
        $montantBase = max(0, $montantBase);

        return $montantBase + $this->computeTimbreAmount($montantBase);
    }

    public function getMontantPaye(): int
    {
        return $this->montantPaye ?? 0;
    }

    public function setMontantPaye(int $montantPaye): static
    {
        $this->montantPaye = max(0, $montantPaye);
        return $this;
    }

    public function getResteAPayer(): int
    {
        return $this->resteAPayer ?? 0;
    }

    public function setResteAPayer(int $resteAPayer): static
    {
        $this->resteAPayer = max(0, $resteAPayer);
        return $this;
    }

    public function getDateEmission(): \DateTimeImmutable
    {
        return $this->dateEmission;
    }

    public function setDateEmission(\DateTimeImmutable $dateEmission): static
    {
        $this->dateEmission = $dateEmission;
        return $this;
    }

    public function getDatePaiement(): ?\DateTimeImmutable
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTimeImmutable $datePaiement): static
    {
        $this->datePaiement = $datePaiement;
        return $this;
    }

    public function getStatut(): StatutFacture
    {
        return $this->statut;
    }

    public function setStatut(StatutFacture $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    /**
     * @return Collection<int, FactureLigne>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(FactureLigne $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setFacture($this);
        }

        return $this;
    }

    public function removeLigne(FactureLigne $ligne): static
    {
        if ($this->lignes->removeElement($ligne)) {
            if ($ligne->getFacture() === $this) {
                $ligne->setFacture(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setFacture($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            if ($paiement->getFacture() === $this) {
                $paiement->setFacture(null);
            }
        }

        return $this;
    }

    public function recalculerMontants(): void
    {
        $montantBase = $this->getBaseTotal();
        $montantTotal = $this->calculerMontantAvecTimbre($montantBase);

        $montantPaye = 0;
        foreach ($this->paiements as $paiement) {
            $montantPaye += $paiement->getMontant();
        }

        $this->montantTotal = $montantTotal;
        $this->montantPaye = $montantPaye;
        $this->resteAPayer = max(0, $montantTotal - $montantPaye);

        if ($this->montantTotal === 0) {
            $this->statut = StatutFacture::BROUILLON;
            $this->datePaiement = null;
            return;
        }

        if ($this->montantPaye <= 0) {
            $this->statut = StatutFacture::NON_PAYE;
            $this->datePaiement = null;
            return;
        }

        if ($this->montantPaye < $this->montantTotal) {
            $this->statut = StatutFacture::PARTIELLEMENT_PAYE;
            $this->datePaiement = null;
            return;
        }

        $this->statut = StatutFacture::PAYE;
        $this->datePaiement = new \DateTimeImmutable();
        $this->resteAPayer = 0;
    }

    private function computeTimbreAmount(int $montantBase): int
    {
        return $montantBase > self::SEUIL_TIMBRE ? self::MONTANT_TIMBRE : 0;
    }
}