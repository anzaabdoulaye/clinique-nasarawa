<?php

namespace App\Entity;

use App\Repository\MedicamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedicamentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Medicament
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nom;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $sku = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $codeBarre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private float $prixUnitaire = 0.0;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $actif = true;

    #[ORM\OneToMany(mappedBy: 'medicament', targetEntity: Lot::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lots;

    public function __construct()
    {
        $this->lots = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getSku(): ?string { return $this->sku; }
    public function setSku(?string $sku): self { $this->sku = $sku; return $this; }

    public function getCodeBarre(): ?string { return $this->codeBarre; }
    public function setCodeBarre(?string $codeBarre): self { $this->codeBarre = $codeBarre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getPrixUnitaire(): float { return $this->prixUnitaire; }
    public function setPrixUnitaire(float $prixUnitaire): self { $this->prixUnitaire = $prixUnitaire; return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): self { $this->actif = $actif; return $this; }

    /** @return Collection<int, Lot> */
    public function getLots(): Collection { return $this->lots; }

    public function addLot(Lot $lot): self
    {
        if (!$this->lots->contains($lot)) {
            $this->lots->add($lot);
            $lot->setMedicament($this);
        }
        return $this;
    }

    public function removeLot(Lot $lot): self
    {
        $this->lots->removeElement($lot);
        return $this;
    }
}
