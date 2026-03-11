<?php

namespace App\Entity;

use App\Repository\TraitementHospitalisationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraitementHospitalisationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TraitementHospitalisation
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'traitements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Hospitalisation $hospitalisation;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $heuresAdministration = [];

    #[ORM\OneToMany(mappedBy: 'traitement', targetEntity: AdministrationTraitement::class, orphanRemoval: true)]
    private Collection $administrations;

    public function __construct()
    {
        $this->administrations = new ArrayCollection();
    }

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
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeImmutable $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getHeuresAdministration(): array
    {
        return $this->heuresAdministration;
    }

    public function setHeuresAdministration(?array $heures): self
    {
        $this->heuresAdministration = $heures ?? [];
        return $this;
    }

    public function getAdministrations(): Collection
    {
        return $this->administrations;
    }

    public function addAdministration(AdministrationTraitement $administration): self
    {
        if (!$this->administrations->contains($administration)) {
            $this->administrations->add($administration);
            $administration->setTraitement($this);
        }

        return $this;
    }

    public function removeAdministration(AdministrationTraitement $administration): self
    {
        if ($this->administrations->removeElement($administration)) {
            if ($administration->getTraitement() === $this) {
                $administration->setTraitement(null);
            }
        }

        return $this;
    }

    public function isScheduledAtHour(int $hour): bool
    {
        return in_array($hour, $this->heuresAdministration, true);
    }

    public function isAdministeredAt(\DateTimeInterface $date, int $hour): bool
{
    foreach ($this->administrations as $administration) {
        if (
            $administration->getDateAdministration()?->format('Y-m-d') === $date->format('Y-m-d')
            && $administration->getHeure() === $hour
        ) {
            return true;
        }
    }

    return false;
}
}