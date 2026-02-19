<?php

namespace App\Entity;


use App\Repository\PrescriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Prescription
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'prescriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private Consultation $consultation;

    #[ORM\Column(length: 100)]
    private string $typePrescription;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $instructions = null;

    #[ORM\OneToMany(mappedBy: 'prescription', targetEntity: PrescriptionLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConsultation(): Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(Consultation $consultation): self
    {
        $this->consultation = $consultation;

        if (!$consultation->getPrescriptions()->contains($this)) {
            $consultation->addPrescription($this);
        }

        return $this;
    }

    public function getTypePrescription(): string
    {
        return $this->typePrescription;
    }

    public function setTypePrescription(string $typePrescription): self
    {
        $this->typePrescription = $typePrescription;

        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @return Collection<int, PrescriptionLigne>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(PrescriptionLigne $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setPrescription($this);
        }

        return $this;
    }

    public function removeLigne(PrescriptionLigne $ligne): self
    {
        if ($this->lignes->removeElement($ligne)) {
            if ($ligne->getPrescription() === $this) {
                $ligne->setPrescription(null);
            }
        }

        return $this;
    }
}
