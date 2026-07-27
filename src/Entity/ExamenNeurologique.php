<?php

namespace App\Entity;

use App\Repository\ExamenNeurologiqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamenNeurologiqueRepository::class)]
class ExamenNeurologique
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'examenNeurologique')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Hospitalisation $hospitalisation;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $conscience = null;

    #[ORM\Column(nullable: true)]
    private ?string $tonusMusculaire = null;

    #[ORM\Column(nullable: true)]
    private ?int $forceMembreSuperieurD = null;

    #[ORM\Column(nullable: true)]
    private ?int $forceMembreSuperieurG = null;

    #[ORM\Column(nullable: true)]
    private ?int $forceMembreInferieurD = null;

    #[ORM\Column(nullable: true)]
    private ?int $forceMembreInferieurG = null;

    #[ORM\Column]
    private bool $babinski = false;

    #[ORM\Column]
    private bool $grasping = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $aphasieType = null;

    #[ORM\Column]
    private bool $agnosie = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $apraxieType = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $troubleSphincteriens = null;

    #[ORM\Column]
    private bool $raideurNuque = false;

    #[ORM\Column]
    private bool $brudzinski = false;

    #[ORM\Column]
    private bool $kernig = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHospitalisation(): Hospitalisation
    {
        return $this->hospitalisation;
    }

    public function setHospitalisation(Hospitalisation $hospitalisation): self
    {
        $this->hospitalisation = $hospitalisation;

        if ($hospitalisation->getExamenNeurologique() !== $this) {
            $hospitalisation->setExamenNeurologique($this);
        }

        return $this;
    }

    public function getConscience(): ?string
    {
        return $this->conscience;
    }

    public function setConscience(?string $conscience): self
    {
        $this->conscience = $conscience;

        return $this;
    }

    public function getTonusMusculaire(): ?string
    {
        return $this->tonusMusculaire;
    }

    public function setTonusMusculaire(?string $tonusMusculaire): self
    {
        $this->tonusMusculaire = $tonusMusculaire;

        return $this;
    }

    public function getForceMembreSuperieurD(): ?int
    {
        return $this->forceMembreSuperieurD;
    }

    public function setForceMembreSuperieurD(?int $value): self
    {
        $this->forceMembreSuperieurD = $value;

        return $this;
    }

    public function getForceMembreSuperieurG(): ?int
    {
        return $this->forceMembreSuperieurG;
    }

    public function setForceMembreSuperieurG(?int $value): self
    {
        $this->forceMembreSuperieurG = $value;

        return $this;
    }

    public function getForceMembreInferieurD(): ?int
    {
        return $this->forceMembreInferieurD;
    }

    public function setForceMembreInferieurD(?int $value): self
    {
        $this->forceMembreInferieurD = $value;

        return $this;
    }

    public function getForceMembreInferieurG(): ?int
    {
        return $this->forceMembreInferieurG;
    }

    public function setForceMembreInferieurG(?int $value): self
    {
        $this->forceMembreInferieurG = $value;

        return $this;
    }

    public function isBabinski(): bool
    {
        return $this->babinski;
    }

    public function setBabinski(bool $babinski): self
    {
        $this->babinski = $babinski;

        return $this;
    }

    public function isGrasping(): bool
    {
        return $this->grasping;
    }

    public function setGrasping(bool $grasping): self
    {
        $this->grasping = $grasping;

        return $this;
    }

    public function getAphasieType(): ?string
    {
        return $this->aphasieType;
    }

    public function setAphasieType(?string $aphasieType): self
    {
        $this->aphasieType = $aphasieType;

        return $this;
    }

    public function isAgnosie(): bool
    {
        return $this->agnosie;
    }

    public function setAgnosie(bool $agnosie): self
    {
        $this->agnosie = $agnosie;

        return $this;
    }

    public function getApraxieType(): ?string
    {
        return $this->apraxieType;
    }

    public function setApraxieType(?string $apraxieType): self
    {
        $this->apraxieType = $apraxieType;

        return $this;
    }

    public function getTroubleSphincteriens(): ?string
    {
        return $this->troubleSphincteriens;
    }

    public function setTroubleSphincteriens(?string $trouble): self
    {
        $this->troubleSphincteriens = $trouble;

        return $this;
    }

    public function isRaideurNuque(): bool
    {
        return $this->raideurNuque;
    }

    public function setRaideurNuque(bool $val): self
    {
        $this->raideurNuque = $val;

        return $this;
    }

    public function isBrudzinski(): bool
    {
        return $this->brudzinski;
    }

    public function setBrudzinski(bool $val): self
    {
        $this->brudzinski = $val;

        return $this;
    }

    public function isKernig(): bool
    {
        return $this->kernig;
    }

    public function setKernig(bool $val): self
    {
        $this->kernig = $val;

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