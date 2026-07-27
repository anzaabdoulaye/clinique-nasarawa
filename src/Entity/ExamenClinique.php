<?php

namespace App\Entity;

use App\Repository\ExamenCliniqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamenCliniqueRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ExamenClinique
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ NOUVEAU CODE :
    #[ORM\ManyToOne(inversedBy: 'examensCliniques')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Hospitalisation $hospitalisation = null;

    // ✅ NOUVEAU CHAMP : Date et heure exacte de la prise des constantes
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $datePrise = null;

    #[ORM\Column(nullable: true)]
    private ?string $tensionArterielle = null;

    #[ORM\Column(nullable: true)]
    private ?int $pouls = null;

    #[ORM\Column(nullable: true)]
    private ?float $temperature = null;

    #[ORM\Column(nullable: true)]
    private ?float $saturationOxygene = null;

    #[ORM\Column(nullable: true)]
    private ?int $frequenceRespiratoire = null;

    #[ORM\Column(nullable: true)]
    private ?float $poids = null;

    #[ORM\Column(nullable: true)]
    private ?float $taille = null;

    #[ORM\Column(nullable: true)]
    private ?float $imc = null;

    #[ORM\Column]
    private bool $deshydratation = false;

    #[ORM\Column]
    private bool $oedeme = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->datePrise = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
public function setHospitalisation(?Hospitalisation $hospitalisation): self
    {
        $this->hospitalisation = $hospitalisation;
        return $this;
    }

    // ✅ NOUVEAU SETTER/GETTER pour la date de prise
    public function getDatePrise(): ?\DateTimeImmutable
    {
        return $this->datePrise;
    }

    public function setDatePrise(\DateTimeImmutable $datePrise): self
    {
        $this->datePrise = $datePrise;
        return $this;
    }

    public function getHospitalisation(): ?Hospitalisation
    {
        return $this->hospitalisation;
    }

    public function getTensionArterielle(): ?string
    {
        return $this->tensionArterielle;
    }

    public function setTensionArterielle(?string $tension): self
    {
        $this->tensionArterielle = $tension;

        return $this;
    }

    public function getPouls(): ?int
    {
        return $this->pouls;
    }

    public function setPouls(?int $pouls): self
    {
        $this->pouls = $pouls;

        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getSaturationOxygene(): ?float
    {
        return $this->saturationOxygene;
    }

    public function setSaturationOxygene(?float $sat): self
    {
        $this->saturationOxygene = $sat;

        return $this;
    }

    public function getFrequenceRespiratoire(): ?int
    {
        return $this->frequenceRespiratoire;
    }

    public function setFrequenceRespiratoire(?int $freq): self
    {
        $this->frequenceRespiratoire = $freq;

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

    public function getTaille(): ?float
    {
        return $this->taille;
    }

    public function setTaille(?float $taille): self
    {
        $this->taille = $taille;

        return $this;
    }

    public function getImc(): ?float
    {
        return $this->imc;
    }

    public function setImc(?float $imc): self
    {
        $this->imc = $imc;

        return $this;
    }

    public function isDeshydratation(): bool
    {
        return $this->deshydratation;
    }

    public function setDeshydratation(bool $val): self
    {
        $this->deshydratation = $val;

        return $this;
    }

    public function isOedeme(): bool
    {
        return $this->oedeme;
    }

    public function setOedeme(bool $val): self
    {
        $this->oedeme = $val;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }
}