<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MedecinRepository;

#[ORM\Entity(repositoryClass: MedecinRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Medecin
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomComplet = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $tauxIsbDefaut = null; // Exemple : 0.05 pour 5%

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact = null;

    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: HonoraireMedecin::class)]
    private Collection $honoraires;

    public function __construct()
    {
        $this->honoraires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomComplet(): ?string
    {
        return $this->nomComplet;
    }

    public function setNomComplet(string $nomComplet): static
    {
        $this->nomComplet = $nomComplet;

        return $this;
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(?string $specialite): static
    {
        $this->specialite = $specialite;

        return $this;
    }

    public function getTauxIsbDefaut(): ?float
    {
        return $this->tauxIsbDefaut;
    }

    public function setTauxIsbDefaut(?float $tauxIsbDefaut): static
    {
        $this->tauxIsbDefaut = $tauxIsbDefaut;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return Collection<int, HonoraireMedecin>
     */
    public function getHonoraires(): Collection
    {
        return $this->honoraires;
    }

    public function addHonoraire(HonoraireMedecin $honoraire): static
    {
        if (!$this->honoraires->contains($honoraire)) {
            $this->honoraires->add($honoraire);
            $honoraire->setMedecin($this);
        }

        return $this;
    }

    public function removeHonoraire(HonoraireMedecin $honoraire): static
    {
        if ($this->honoraires->removeElement($honoraire)) {
            // set the owning side to null (unless already changed)
            if ($honoraire->getMedecin() === $this) {
                $honoraire->setMedecin(null);
            }
        }

        return $this;
    }
}