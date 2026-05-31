<?php

namespace App\Entity;

use App\Repository\ResultatLaboratoireLigneRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultatLaboratoireLigneRepository::class)]
class ResultatLaboratoireLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ResultatLaboratoire $resultatLaboratoire = null;

    #[ORM\Column(length: 255)]
    private string $demande;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resultat = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $valeurNormale = null;

    #[ORM\Column(type: 'integer')]
    private int $ordre = 1;

    /**
     * Nouveau champ pour catégoriser les éléments (ex: Macroscopie, Examen microscopique)
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $groupe = null;

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

    public function getDemande(): string
    {
        return $this->demande;
    }

    public function setDemande(string $demande): static
    {
        $this->demande = $demande;
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

    public function getValeurNormale(): ?string
    {
        return $this->valeurNormale;
    }

    public function setValeurNormale(?string $valeurNormale): static
    {
        $this->valeurNormale = $valeurNormale;
        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function getGroupe(): ?string
    {
        return $this->groupe;
    }

    public function setGroupe(?string $groupe): static
    {
        $this->groupe = $groupe;
        return $this;
    }
}