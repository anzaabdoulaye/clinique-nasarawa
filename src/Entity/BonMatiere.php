<?php

namespace App\Entity;

use App\Entity\TimestampableTrait as EntityTimestampableTrait;
use App\Enum\MotifMouvement;
use App\Enum\StatutBonMatiere;
use App\Enum\TypeBonMatiere;
use App\Repository\BonMatiereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BonMatiereRepository::class)]
#[ORM\HasLifecycleCallbacks]
class BonMatiere
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $numero;

    #[ORM\Column(enumType: TypeBonMatiere::class)]
    private TypeBonMatiere $type;

    #[ORM\Column(enumType: MotifMouvement::class)]
    private MotifMouvement $motif;

    #[ORM\Column(enumType: StatutBonMatiere::class)]
    private StatutBonMatiere $statut = StatutBonMatiere::BROUILLON;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateBon;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referenceExterne = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observation = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $impactStock = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $ordonnateur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $creePar = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Vente $vente = null;

    #[ORM\OneToMany(mappedBy: 'bon', targetEntity: BonMatiereLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->dateBon = new \DateTimeImmutable();
        $this->statut = StatutBonMatiere::BROUILLON;
        $this->impactStock = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getType(): TypeBonMatiere
    {
        return $this->type;
    }

    public function setType(TypeBonMatiere $type): static
    {
        $this->type = $type;

        if ($type === TypeBonMatiere::SORTIE_PROVISOIRE) {
            $this->impactStock = false;
        }

        return $this;
    }

    public function getMotif(): MotifMouvement
    {
        return $this->motif;
    }

    public function setMotif(MotifMouvement $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getStatut(): StatutBonMatiere
    {
        return $this->statut;
    }

    public function setStatut(StatutBonMatiere $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateBon(): \DateTimeImmutable
    {
        return $this->dateBon;
    }

    public function setDateBon(\DateTimeImmutable $dateBon): static
    {
        $this->dateBon = $dateBon;

        return $this;
    }

    public function getReferenceExterne(): ?string
    {
        return $this->referenceExterne;
    }

    public function setReferenceExterne(?string $referenceExterne): static
    {
        $this->referenceExterne = $referenceExterne;

        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;

        return $this;
    }

    public function isImpactStock(): bool
    {
        return $this->impactStock;
    }

    public function setImpactStock(bool $impactStock): static
    {
        $this->impactStock = $impactStock;

        return $this;
    }

    public function getOrdonnateur(): ?Utilisateur
    {
        return $this->ordonnateur;
    }

    public function setOrdonnateur(?Utilisateur $ordonnateur): static
    {
        $this->ordonnateur = $ordonnateur;

        return $this;
    }

    public function getCreePar(): ?Utilisateur
    {
        return $this->creePar;
    }

    public function setCreePar(?Utilisateur $creePar): static
    {
        $this->creePar = $creePar;

        return $this;
    }

    public function getVente(): ?Vente
    {
        return $this->vente;
    }

    public function setVente(?Vente $vente): static
    {
        $this->vente = $vente;

        return $this;
    }

    /**
     * @return Collection<int, BonMatiereLigne>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(BonMatiereLigne $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setBon($this);
        }

        return $this;
    }

    public function removeLigne(BonMatiereLigne $ligne): static
    {
        if ($this->lignes->removeElement($ligne)) {
            if ($ligne->getBon() === $this) {
                $ligne->setBon(null);
            }
        }

        return $this;
    }

    public function getTotalLignes(): int
    {
        return $this->lignes->count();
    }

    public function getQuantiteTotale(): int
    {
        return array_reduce(
            $this->lignes->toArray(),
            fn (int $carry, BonMatiereLigne $ligne) => $carry + $ligne->getQuantite(),
            0
        );
    }

    private function prefixe(TypeBonMatiere $type): string
    {
        return match ($type) {
            TypeBonMatiere::ENTREE => 'BE',
            TypeBonMatiere::SORTIE_DEFINITIVE => 'BSD',
            TypeBonMatiere::SORTIE_PROVISOIRE => 'BSP',
        };
    }
}