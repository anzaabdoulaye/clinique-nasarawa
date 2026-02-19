<?php

namespace App\Entity;


use App\Repository\PrescriptionLigneRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionLigneRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PrescriptionLigne
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private Prescription $prescription;

    #[ORM\Column]
    private int $quantite;

    #[ORM\Column(length: 255)]
    private string $posologie;

    #[ORM\Column(nullable: true)]
    private ?int $duree = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrescription(): Prescription
    {
        return $this->prescription;
    }

    public function setPrescription(Prescription $prescription): self
    {
        $this->prescription = $prescription;

        if (!$prescription->getLignes()->contains($this)) {
            $prescription->addLigne($this);
        }

        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPosologie(): string
    {
        return $this->posologie;
    }

    public function setPosologie(string $posologie): self
    {
        $this->posologie = $posologie;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): self
    {
        $this->duree = $duree;

        return $this;
    }
}
