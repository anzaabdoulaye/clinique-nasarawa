<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class BonExamenLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private BonExamen $bon;

    #[ORM\Column(length: 255)]
    private string $libelle = '';

    #[ORM\Column(nullable: true)]
    private ?bool $urgence = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    // Résultat structuré simple
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resultatValeur = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $valeursNormales = null;

    // Résultat libre (texte)
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resultatTexte = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resultatSaisiLe = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Utilisateur $resultatSaisiPar = null;

    // Pièce jointe (chemin fichier) — simple
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pieceJointePath = null;

    // Prix pour la facturation (quand résultats disponibles)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $prixUnitaire = null;

    public function getId(): ?int { return $this->id; }

    public function getBon(): BonExamen { return $this->bon; }
    public function setBon(BonExamen $bon): self { $this->bon = $bon; return $this; }

    public function getLibelle(): string { return $this->libelle; }
    public function setLibelle(string $libelle): self { $this->libelle = $libelle; return $this; }

    public function isUrgence(): ?bool { return $this->urgence; }
    public function setUrgence(?bool $urgence): self { $this->urgence = $urgence; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note; return $this; }

    public function getResultatValeur(): ?string { return $this->resultatValeur; }
    public function setResultatValeur(?string $resultatValeur): self { $this->resultatValeur = $resultatValeur; return $this; }

    public function getUnite(): ?string { return $this->unite; }
    public function setUnite(?string $unite): self { $this->unite = $unite; return $this; }

    public function getValeursNormales(): ?string { return $this->valeursNormales; }
    public function setValeursNormales(?string $valeursNormales): self { $this->valeursNormales = $valeursNormales; return $this; }

    public function getResultatTexte(): ?string { return $this->resultatTexte; }
    public function setResultatTexte(?string $resultatTexte): self { $this->resultatTexte = $resultatTexte; return $this; }

    public function getResultatSaisiLe(): ?\DateTimeImmutable { return $this->resultatSaisiLe; }
    public function setResultatSaisiLe(?\DateTimeImmutable $d): self { $this->resultatSaisiLe = $d; return $this; }

    public function getResultatSaisiPar(): ?Utilisateur { return $this->resultatSaisiPar; }
    public function setResultatSaisiPar(?Utilisateur $u): self { $this->resultatSaisiPar = $u; return $this; }

    public function getPieceJointePath(): ?string { return $this->pieceJointePath; }
    public function setPieceJointePath(?string $p): self { $this->pieceJointePath = $p; return $this; }

    public function getPrixUnitaire(): ?string { return $this->prixUnitaire; }
    public function setPrixUnitaire(?string $prixUnitaire): self { $this->prixUnitaire = $prixUnitaire; return $this; }

    public function isResultatValide(): bool
    {
        return ($this->resultatValeur !== null && trim($this->resultatValeur) !== '')
            || ($this->resultatTexte !== null && trim($this->resultatTexte) !== '')
            || ($this->pieceJointePath !== null && trim($this->pieceJointePath) !== '');
    }
}