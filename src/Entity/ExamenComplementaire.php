<?php

namespace App\Entity;

use App\Enum\TypeExamenComplementaire;
use App\Repository\ExamenComplementaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamenComplementaireRepository::class)]
class ExamenComplementaire
{
     use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'examensComplementaires')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Hospitalisation $hospitalisation;

    #[ORM\Column(enumType: TypeExamenComplementaire::class)]
    private TypeExamenComplementaire $type;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resultat = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateExamen = null;


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

        if (!$hospitalisation->getExamensComplementaires()->contains($this)) {
            $hospitalisation->addExamenComplementaire($this);
        }

        return $this;
    }

    public function getType(): TypeExamenComplementaire
    {
        return $this->type;
    }

    public function setType(TypeExamenComplementaire $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getResultat(): ?string
    {
        return $this->resultat;
    }

    public function setResultat(?string $resultat): self
    {
        $this->resultat = $resultat;

        return $this;
    }

    public function getDateExamen(): ?\DateTimeImmutable
    {
        return $this->dateExamen;
    }

    public function setDateExamen(?\DateTimeImmutable $dateExamen): self
    {
        $this->dateExamen = $dateExamen;

        return $this;
    }
 }