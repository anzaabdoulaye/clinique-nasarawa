<?php

namespace App\Entity;

use App\Repository\VenteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VenteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Vente
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column]
    private float $total = 0.0;

    #[ORM\ManyToOne(inversedBy: 'ventes')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $vendeur = null;

    #[ORM\OneToMany(mappedBy: 'vente', targetEntity: VenteLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    #[ORM\OneToMany(mappedBy: 'vente', targetEntity: BonMatiere::class)]
    private Collection $bonsMatiere;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->date = new \DateTimeImmutable();
        $this->bonsMatiere = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getDate(): \DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $date): self { $this->date = $date; return $this; }

    public function getTotal(): float { return $this->total; }
    public function setTotal(float $total): self { $this->total = $total; return $this; }

    public function getVendeur(): ?Utilisateur { return $this->vendeur; }
    public function setVendeur(?Utilisateur $vendeur): self { $this->vendeur = $vendeur; return $this; }

    /** @return Collection<int, VenteLigne> */
    public function getLignes(): Collection { return $this->lignes; }

    public function addLigne(VenteLigne $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setVente($this);
            $this->recalcTotal();
        }
        return $this;
    }

    public function removeLigne(VenteLigne $ligne): self
    {
        $this->lignes->removeElement($ligne);
        $this->recalcTotal();
        return $this;
    }

    public function recalcTotal(): void
    {
        $sum = 0.0;
        foreach ($this->lignes as $l) {
            $sum += $l->getSousTotal();
        }
        $this->total = $sum;
    }

        /**
     * @return Collection<int, BonMatiere>
     */
    public function getBonsMatiere(): Collection
    {
        return $this->bonsMatiere;
    }
}
