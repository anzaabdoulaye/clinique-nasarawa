<?php

namespace App\Entity;

use App\Repository\ResultatLaboratoireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultatLaboratoireRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ResultatLaboratoire
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'resultatLaboratoire')]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?PrescriptionPrestation $prescriptionPrestation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resultat = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $conclusion = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateValidation = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $validePar = null;

    #[ORM\OneToMany(mappedBy: 'resultatLaboratoire', targetEntity: ResultatLaboratoireLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    // À insérer dans les propriétés de la classe ResultatLaboratoire
    #[ORM\OneToMany(mappedBy: 'resultatLaboratoire', targetEntity: ResultatAntibiogramme::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $antibiogrammes;



    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->antibiogrammes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrescriptionPrestation(): ?PrescriptionPrestation
    {
        return $this->prescriptionPrestation;
    }

    public function setPrescriptionPrestation(?PrescriptionPrestation $prescriptionPrestation): static
    {
        $this->prescriptionPrestation = $prescriptionPrestation;
        return $this;
    }

    public function getResultat(): ?string
    {
        return $this->resultat;
    }

    public function setResultat(?string $resultat): static
    {
        $this->resultat = $resultat;
        return $this;
    }

    public function getConclusion(): ?string
    {
        return $this->conclusion;
    }

    public function setConclusion(?string $conclusion): static
    {
        $this->conclusion = $conclusion;
        return $this;
    }

    public function getDateValidation(): ?\DateTimeImmutable
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeImmutable $dateValidation): static
    {
        $this->dateValidation = $dateValidation;
        return $this;
    }

    public function getValidePar(): ?string
    {
        return $this->validePar;
    }

    public function setValidePar(?string $validePar): static
    {
        $this->validePar = $validePar;
        return $this;
    }
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(ResultatLaboratoireLigne $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setResultatLaboratoire($this);
        }

        return $this;
    }

    public function removeLigne(ResultatLaboratoireLigne $ligne): static
    {
        if ($this->lignes->removeElement($ligne)) {
            if ($ligne->getResultatLaboratoire() === $this) {
                $ligne->setResultatLaboratoire(null);
            }
        }

        return $this;
    }
    
    // À ajouter en bas du fichier (Getters / Setters)
    /**
     * @return Collection<int, ResultatAntibiogramme>
     */
    public function getAntibiogrammes(): Collection
    {
        return $this->antibiogrammes;
    }

    public function addAntibiogramme(ResultatAntibiogramme $antibiogramme): static
    {
        if (!$this->antibiogrammes->contains($antibiogramme)) {
            $this->antibiogrammes->add($antibiogramme);
            $antibiogramme->setResultatLaboratoire($this);
        }
        return $this;
    }

    public function removeAntibiogramme(ResultatAntibiogramme $antibiogramme): static
    {
        if ($this->antibiogrammes->removeElement($antibiogramme)) {
            if ($antibiogramme->getResultatLaboratoire() === $this) {
                $antibiogramme->setResultatLaboratoire(null);
            }
        }
        return $this;
    }
}