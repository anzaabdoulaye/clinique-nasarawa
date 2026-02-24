<?php

namespace App\Entity;

use App\Enum\StatutUtilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(length: 100)]
    private string $prenom;

    #[ORM\Column(length: 180, unique: true)]
    private string $username;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(enumType: StatutUtilisateur::class)]
    private StatutUtilisateur $statut = StatutUtilisateur::ACTIF;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $forcePasswordChange = true;

    #[ORM\ManyToOne(inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
private ?ServiceMedical $serviceMedical = null;  

    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: RendezVous::class)]
    private Collection $rendezVous;

    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: Consultation::class)]
    private Collection $consultations;

    #[ORM\OneToMany(mappedBy: 'medecinReferent', targetEntity: Hospitalisation::class)]
    private Collection $hospitalisations;

    public function __construct()
    {
        $this->rendezVous = new ArrayCollection();
        $this->consultations = new ArrayCollection();
        $this->hospitalisations = new ArrayCollection();
    }

    public function isForcePasswordChange(): bool
    {
        return $this->forcePasswordChange;
    }

    public function setForcePasswordChange(bool $force): self
    {
        $this->forcePasswordChange = $force;
        return $this;
    }
    public function getId(): ?int { return $this->id; }

    /** @return Collection<int, RendezVous> */
    public function getRendezVous(): Collection
    {
        return $this->rendezVous;
    }

    public function addRendezVous(RendezVous $rendezVous): self
    {
        if (!$this->rendezVous->contains($rendezVous)) {
            $this->rendezVous->add($rendezVous);
            if (method_exists($rendezVous, 'setMedecin')) {
                $rendezVous->setMedecin($this);
            }
        }

        return $this;
    }

    public function removeRendezVous(RendezVous $rendezVous): self
    {
        $this->rendezVous->removeElement($rendezVous);

        return $this;
    }

    /** @return Collection<int, Consultation> */
    public function getConsultations(): Collection
    {
        return $this->consultations;
    }

    public function addConsultation(Consultation $consultation): self
    {
        if (!$this->consultations->contains($consultation)) {
            $this->consultations->add($consultation);
            if (method_exists($consultation, 'setMedecin')) {
                $consultation->setMedecin($this);
            }
        }

        return $this;
    }

    public function removeConsultation(Consultation $consultation): self
    {
        $this->consultations->removeElement($consultation);

        return $this;
    }

    /** @return Collection<int, Hospitalisation> */
    public function getHospitalisations(): Collection
    {
        return $this->hospitalisations;
    }

    public function addHospitalisation(Hospitalisation $hospitalisation): self
    {
        if (!$this->hospitalisations->contains($hospitalisation)) {
            $this->hospitalisations->add($hospitalisation);
            if (method_exists($hospitalisation, 'setMedecinReferent')) {
                $hospitalisation->setMedecinReferent($this);
            }
        }

        return $this;
    }

    public function removeHospitalisation(Hospitalisation $hospitalisation): self
    {
        $this->hospitalisations->removeElement($hospitalisation);

        return $this;
    }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }


    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_values(array_unique($roles));
    }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function eraseCredentials(): void {}

    public function getStatut(): StatutUtilisateur
    {
        return $this->statut;
    }

    public function setStatut(StatutUtilisateur $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

   public function getServiceMedical(): ?ServiceMedical
    {
        return $this->serviceMedical;
    }

    public function setServiceMedical(?ServiceMedical $serviceMedical): self
    {
        $this->serviceMedical = $serviceMedical;
        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getNomComplet(): string
{
    return trim(($this->nom ?? '') . ' ' . ($this->prenom ?? ''));
}
}
