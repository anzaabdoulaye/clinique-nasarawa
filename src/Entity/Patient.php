<?php

namespace App\Entity;



use App\Repository\PatientRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Patient
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[Assert\NotBlank]
    #[ORM\Column(length: 100)]
    private string $nom;

    #[Assert\NotBlank]
    #[ORM\Column(length: 100)]
    private string $prenom;

    #[Assert\LessThanOrEqual('today')]
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private \DateTimeImmutable $dateNaissance;

    #[Assert\Positive]
    #[ORM\Column(length: 30)]
    private string $telephone;

    #[ORM\Column(length: 30, unique: true)]
    private ?string $code = null;


    #[ORM\PrePersist]
    public function generateCodeIfEmpty(): void
    {
        if ($this->code) {
            return;
        }

        // Exemple: PAT-20260223-8F3A1C
        $date = (new \DateTimeImmutable())->format('Ymd');
        $rand = strtoupper(bin2hex(random_bytes(3))); // 6 chars

        $this->code = sprintf('PAT-%s-%s', $date, $rand);
    }

     // Patient = inverse side
    #[ORM\OneToOne(mappedBy: 'patient', targetEntity: DossierMedical::class, cascade: ['persist', 'remove'])]
    private ?DossierMedical $dossierMedical = null;

     public function getDossierMedical(): ?DossierMedical
    {
        return $this->dossierMedical;
    }

    public function setDossierMedical(?DossierMedical $dossierMedical): self
    {
        $this->dossierMedical = $dossierMedical;

        // synchroniser les 2 côtés de la relation
        if ($dossierMedical && $dossierMedical->getPatient() !== $this) {
            $dossierMedical->setPatient($this);
        }

        return $this;
    }

     #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        // 1) Générer le code si vide
        if (!$this->code) {
            $date = (new \DateTimeImmutable())->format('Ymd');
            $rand = strtoupper(bin2hex(random_bytes(3))); // 6 chars
            $this->code = sprintf('PAT-%s-%s', $date, $rand);
        }

        if (!$this->dossierMedical) {
            $dossier = new DossierMedical();
            $dossier->setPatient($this);

            $dossier->setNumeroDossier('DOS-' . $this->getCode());

            $this->setDossierMedical($dossier);
        }

        $this->syncMedicalDataToDossier();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->syncMedicalDataToDossier();
    }

    private function syncMedicalDataToDossier(): void
    {
        $dossier = $this->dossierMedical;

        if ($dossier === null) {
            return;
        }

        $dossier->setGroupeSanguin($this->groupeSanguin);
        $dossier->setAllergies($this->allergies);
        $dossier->setAntecedentsMedicaux($this->antecedentsMedicaux);
        $dossier->setAntecedentsChirurgicaux($this->antecedentsChirurgicaux);
        $dossier->setMaladiesChroniques($this->maladiesChroniques);
        $dossier->setTraitementEnCours($this->traitementEnCours);
        $dossier->setHandicap($this->handicap);
        $dossier->setGrossesse($this->grossesse);
    }

    #[ORM\Column(length: 10)]
    #[Assert\Choice(choices: ['M', 'F'])]
    private ?string $sexe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Choice(choices: ['A+','A-','B+','B-','AB+','AB-','O+','O-'])]
    private ?string $groupeSanguin = null;

    // --- informations de contact d'urgence ---
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $emergencyContactName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $emergencyContactRelation = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $emergencyContactPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emergencyContactAddress = null;

    // --- données médicales générales ---
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $taille = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $poids = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $allergies = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $antecedentsMedicaux = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $antecedentsChirurgicaux = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $maladiesChroniques = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $traitementEnCours = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $handicap = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $grossesse = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $code): self { $this->code = $code; return $this; }

    public function getDateNaissance(): \DateTimeImmutable
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeImmutable $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }



    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): self
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getGroupeSanguin(): ?string
    {
        return $this->groupeSanguin;
    }

    public function setGroupeSanguin(?string $groupeSanguin): self
    {
        $this->groupeSanguin = $groupeSanguin;

        return $this;
    }

    // emergency contact getters/setters
    public function getEmergencyContactName(): ?string
    {
        return $this->emergencyContactName;
    }

    public function setEmergencyContactName(?string $name): self
    {
        $this->emergencyContactName = $name;
        return $this;
    }

    public function getEmergencyContactRelation(): ?string
    {
        return $this->emergencyContactRelation;
    }

    public function setEmergencyContactRelation(?string $relation): self
    {
        $this->emergencyContactRelation = $relation;
        return $this;
    }

    public function getEmergencyContactPhone(): ?string
    {
        return $this->emergencyContactPhone;
    }

    public function setEmergencyContactPhone(?string $phone): self
    {
        $this->emergencyContactPhone = $phone;
        return $this;
    }

    public function getEmergencyContactAddress(): ?string
    {
        return $this->emergencyContactAddress;
    }

    public function setEmergencyContactAddress(?string $address): self
    {
        $this->emergencyContactAddress = $address;
        return $this;
    }

    // medical info getters/setters
    public function getTaille(): ?float
    {
        return $this->taille;
    }

    public function setTaille(?float $taille): self
    {
        $this->taille = $taille;
        return $this;
    }

    public function getPoids(): ?float
    {
        return $this->poids;
    }

    public function setPoids(?float $poids): self
    {
        $this->poids = $poids;
        return $this;
    }

    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function setAllergies(?string $allergies): self
    {
        $this->allergies = $allergies;
        return $this;
    }

    public function getAntecedentsMedicaux(): ?string
    {
        return $this->antecedentsMedicaux;
    }

    public function setAntecedentsMedicaux(?string $antecedents): self
    {
        $this->antecedentsMedicaux = $antecedents;
        return $this;
    }

    public function getAntecedentsChirurgicaux(): ?string
    {
        return $this->antecedentsChirurgicaux;
    }

    public function setAntecedentsChirurgicaux(?string $antecedents): self
    {
        $this->antecedentsChirurgicaux = $antecedents;
        return $this;
    }

    public function getMaladiesChroniques(): ?string
    {
        return $this->maladiesChroniques;
    }

    public function setMaladiesChroniques(?string $maladies): self
    {
        $this->maladiesChroniques = $maladies;
        return $this;
    }

    public function getTraitementEnCours(): ?string
    {
        return $this->traitementEnCours;
    }

    public function setTraitementEnCours(?string $traitement): self
    {
        $this->traitementEnCours = $traitement;
        return $this;
    }

    public function getHandicap(): ?string
    {
        return $this->handicap;
    }

    public function setHandicap(?string $handicap): self
    {
        $this->handicap = $handicap;
        return $this;
    }

    public function isGrossesse(): ?bool
    {
        return $this->grossesse;
    }

    public function setGrossesse(?bool $grossesse): self
    {
        $this->grossesse = $grossesse;
        return $this;
    }
}
