<?php

namespace App\Entity;

use App\Repository\TraitementHospitalisationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraitementHospitalisationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TraitementHospitalisation
{
    private const ADMINISTRATION_WINDOW_IN_HOURS = 1;

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

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $planification = [];

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

   // 1. On remplace isScheduledAtHour par isScheduledAt (qui prend en compte la date)
    public function isScheduledAt(\DateTimeInterface $date, int $hour): bool
    {
        $heuresPourDate = $this->getHeuresPourDate($date);
        return in_array($hour, $heuresPourDate, true);
    }

    // 2. On met à jour isWithinPeriodAt pour qu'elle utilise isScheduledAt
    public function isWithinPeriodAt(
        \DateTimeInterface $date,
        int $hour,
        ?\DateTimeInterface $reference = null
    ): bool {
        if (!$this->isScheduledAt($date, $hour)) {
            return false;
        }

        $reference ??= new \DateTimeImmutable();
        $targetDate = $date->format('Y-m-d');

        if ($this->dateDebut && $targetDate < $this->dateDebut->format('Y-m-d')) {
            return false;
        }

        if ($this->dateFin && $targetDate > $this->dateFin->format('Y-m-d')) {
            return false;
        }

        $scheduledAt = new \DateTimeImmutable(sprintf('%s %02d:00:00', $targetDate, $hour));

        return $scheduledAt <= $reference;
    }

    // 3. On met à jour isLateSlotAt pour qu'elle utilise isScheduledAt
    public function isLateSlotAt(
        \DateTimeInterface $date,
        int $hour,
        ?\DateTimeInterface $reference = null
    ): bool {
        if (!$this->isScheduledAt($date, $hour)) {
            return false;
        }

        $reference ??= new \DateTimeImmutable();
        $deadlineAt = $this->getAdministrationDeadlineAt($date, $hour);

        return $deadlineAt <= $reference;
    }

    public function getScheduledAt(\DateTimeInterface $date, int $hour): \DateTimeImmutable
    {
        return new \DateTimeImmutable(sprintf('%s %02d:00:00', $date->format('Y-m-d'), $hour));
    }

    public function getAdministrationDeadlineAt(\DateTimeInterface $date, int $hour): \DateTimeImmutable
    {
        return $this->getScheduledAt($date, $hour)->modify(sprintf('+%d hour', self::ADMINISTRATION_WINDOW_IN_HOURS));
    }

    public function isAdministrationWindowOpenAt(
        \DateTimeInterface $date,
        int $hour,
        ?\DateTimeInterface $reference = null
    ): bool {
        if (!$this->isWithinPeriodAt($date, $hour, $reference)) {
            return false;
        }

        $reference ??= new \DateTimeImmutable();

        return $reference < $this->getAdministrationDeadlineAt($date, $hour);
    }

    public function getAdministrationAt(\DateTimeInterface $date, int $hour): ?AdministrationTraitement
    {
        foreach ($this->administrations as $administration) {
            if (
                $administration->getDateAdministration()?->format('Y-m-d') === $date->format('Y-m-d')
                && $administration->getHeure() === $hour
            ) {
                return $administration;
            }
        }

        return null;
    }


    public function isLateAdministrationAt(\DateTimeInterface $date, int $hour): bool
    {
        $administration = $this->getAdministrationAt($date, $hour);

        return $administration?->getStatut() === 'retard';
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

    public function getPlanification(): array
{
    return $this->planification ?? [];
}

public function setPlanification(?array $planification): self
{
    $this->planification = $planification ?? [];
    return $this;
}

/**
 * Retourne les heures d'administration pour une date donnée
 */
public function getHeuresPourDate(\DateTimeInterface $date): array
{
    $dateKey = $date->format('Y-m-d');
    $planif = $this->getPlanification();

    if (isset($planif[$dateKey]) && is_array($planif[$dateKey])) {
        return $planif[$dateKey];
    }

    // Fallback sur l'ancien système (heuresAdministration) si la planification est vide pour cette date
    // ET que le traitement est actif à cette date
    if (!empty($this->heuresAdministration) && $this->isActiveOnDate($date)) {
        return $this->heuresAdministration;
    }

    return [];
}

/**
 * Vérifie si le traitement est actif à une date donnée (entre dateDebut et dateFin)
 */
public function isActiveOnDate(\DateTimeInterface $date): bool
{
    if ($this->dateDebut && $date < $this->dateDebut) return false;
    if ($this->dateFin && $date > $this->dateFin) return false;
    return true;
}






    /**
     * Retourne toutes les dates planifiées (clés du tableau)
     */
    public function getDatesPlanifiees(): array
    {
        $dates = array_keys($this->getPlanification());
        sort($dates);
        return $dates;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updatePlanificationFromLegacy(): void
    {
        if (empty($this->planification) && !empty($this->heuresAdministration) && $this->dateDebut && $this->dateFin) {
            // Générer la planification pour chaque jour de la période
            $period = new \DatePeriod(
                $this->dateDebut,
                new \DateInterval('P1D'),
                $this->dateFin->modify('+1 day')
            );
            $planif = [];
            foreach ($period as $date) {
                $planif[$date->format('Y-m-d')] = $this->heuresAdministration;
            }
            $this->planification = $planif;
        }
    }
}