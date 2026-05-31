<?php

namespace App\Entity;

use App\Repository\ResultatAntibiogrammeLigneRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultatAntibiogrammeLigneRepository::class)]
class ResultatAntibiogrammeLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ResultatAntibiogramme $resultatAntibiogramme = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Antibiotique $antibiotique = null;

    /**
     * Stocke les valeurs de sensibilité (ex: S, I, R)
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sensibilite = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResultatAntibiogramme(): ?ResultatAntibiogramme
    {
        return $this->resultatAntibiogramme;
    }

    public function setResultatAntibiogramme(?ResultatAntibiogramme $resultatAntibiogramme): static
    {
        $this->resultatAntibiogramme = $resultatAntibiogramme;
        return $this;
    }

    public function getAntibiotique(): ?Antibiotique
    {
        return $this->antibiotique;
    }

    public function setAntibiotique(?Antibiotique $antibiotique): static
    {
        $this->antibiotique = $antibiotique;
        return $this;
    }

    public function getSensibilite(): ?string
    {
        return $this->sensibilite;
    }

    public function setSensibilite(?string $sensibilite): static
    {
        $this->sensibilite = $sensibilite;
        return $this;
    }
}