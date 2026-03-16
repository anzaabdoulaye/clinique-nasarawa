<?php

namespace App\Entity;

use App\Enum\ModePaiement;
use App\Repository\PaiementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Paiement
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'paiements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Facture $facture = null;

    #[ORM\Column(type: 'integer')]
    private int $montant = 0;

    #[ORM\Column(enumType: ModePaiement::class)]
    private ?ModePaiement $mode = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $payeLe;

    public function __construct()
    {
        $this->payeLe = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (!isset($this->payeLe)) {
            $this->payeLe = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): static
    {
        $this->facture = $facture;
        return $this;
    }

    public function getMontant(): int
    {
        return $this->montant;
    }

    public function setMontant(int $montant): static
    {
        $this->montant = max(0, $montant);
        return $this;
    }

    public function getMode(): ?ModePaiement
    {
        return $this->mode;
    }

    public function setMode(?ModePaiement $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    public function getPayeLe(): \DateTimeImmutable
    {
        return $this->payeLe;
    }

    public function setPayeLe(\DateTimeImmutable $payeLe): static
    {
        $this->payeLe = $payeLe;
        return $this;
    }
}