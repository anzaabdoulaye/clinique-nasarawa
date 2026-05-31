<?php

namespace App\Entity;

use App\Repository\ResultatAntibiogrammeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultatAntibiogrammeRepository::class)]
class ResultatAntibiogramme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'antibiogrammes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ResultatLaboratoire $resultatLaboratoire = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Germe $germe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeration = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\OneToMany(mappedBy: 'resultatAntibiogramme', targetEntity: ResultatAntibiogrammeLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResultatLaboratoire(): ?ResultatLaboratoire
    {
        return $this->resultatLaboratoire;
    }

    public function setResultatLaboratoire(?ResultatLaboratoire $resultatLaboratoire): static
    {
        $this->resultatLaboratoire = $resultatLaboratoire;
        return $this;
    }

    public function getGerme(): ?Germe
    {
        return $this->germe;
    }

    public function setGerme(?Germe $germe): static
    {
        $this->germe = $germe;
        return $this;
    }

    public function getNumeration(): ?string
    {
        return $this->numeration;
    }

    public function setNumeration(?string $numeration): static
    {
        $this->numeration = $numeration;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    /**
     * @return Collection<int, ResultatAntibiogrammeLigne>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(ResultatAntibiogrammeLigne $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setResultatAntibiogramme($this);
        }
        return $this;
    }

    public function removeLigne(ResultatAntibiogrammeLigne $ligne): static
    {
        if ($this->lignes->removeElement($ligne)) {
            if ($ligne->getResultatAntibiogramme() === $this) {
                $ligne->setResultatAntibiogramme(null);
            }
        }
        return $this;
    }
}