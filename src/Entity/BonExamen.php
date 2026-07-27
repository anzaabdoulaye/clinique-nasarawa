<?php

namespace App\Entity;

use App\Enum\StatutBonExamen;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class BonExamen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Consultation nullable (cas labo externe)
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Consultation $consultation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Patient $patient;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Utilisateur $medecin = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateDemande;

    #[ORM\Column(enumType: StatutBonExamen::class)]
    private StatutBonExamen $statut = StatutBonExamen::DEMANDE;

    // Token pour QR / accès rapide
    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\OneToMany(mappedBy: 'bon', targetEntity: BonExamenLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    public function __construct()
    {
        $this->dateDemande = new \DateTimeImmutable();
        $this->token = bin2hex(random_bytes(16));
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getConsultation(): ?Consultation { return $this->consultation; }
    public function setConsultation(?Consultation $consultation): self { $this->consultation = $consultation; return $this; }

    public function getPatient(): Patient { return $this->patient; }
    public function setPatient(Patient $patient): self { $this->patient = $patient; return $this; }

    public function getMedecin(): ?Utilisateur { return $this->medecin; }
    public function setMedecin(?Utilisateur $medecin): self { $this->medecin = $medecin; return $this; }

    public function getDateDemande(): \DateTimeImmutable { return $this->dateDemande; }
    public function setDateDemande(\DateTimeImmutable $dateDemande): self { $this->dateDemande = $dateDemande; return $this; }

    public function getStatut(): StatutBonExamen { return $this->statut; }
    public function setStatut(StatutBonExamen $statut): self { $this->statut = $statut; return $this; }

    public function getToken(): string { return $this->token; }
    public function setToken(string $token): self { $this->token = $token; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note; return $this; }

    /** @return Collection<int, BonExamenLigne> */
    public function getLignes(): Collection { return $this->lignes; }

    public function addLigne(BonExamenLigne $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setBon($this);
        }
        return $this;
    }

    public function removeLigne(BonExamenLigne $ligne): self
    {
        $this->lignes->removeElement($ligne);
        return $this;
    }

    public function recomputeStatut(): void
    {
        if ($this->statut === StatutBonExamen::ANNULE) return;

        $total = $this->lignes->count();
        if ($total === 0) {
            $this->statut = StatutBonExamen::DEMANDE;
            return;
        }

        $done = 0;
        foreach ($this->lignes as $l) {
            if ($l->isResultatValide()) $done++;
        }

        if ($done === 0) $this->statut = StatutBonExamen::DEMANDE;
        elseif ($done < $total) $this->statut = StatutBonExamen::PARTIEL;
        else $this->statut = StatutBonExamen::RESULTATS_DISPONIBLES;
    }
}