<?php

namespace App\Entity;


use App\Repository\ServiceMedicalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceMedicalRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ServiceMedical
{

    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

     #[ORM\Column(length: 150)]
    private string $libelle;

    #[ORM\OneToMany(mappedBy: 'serviceMedical', targetEntity: Utilisateur::class)]
    private Collection $utilisateurs;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    /** @return Collection<int, Utilisateur> */
    public function getUtilisateurs(): Collection
    {
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateur $utilisateur): self
    {
        if (!$this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs->add($utilisateur);
            if (method_exists($utilisateur, 'setServiceMedical')) {
                $utilisateur->setServiceMedical($this);
            }
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        if ($this->utilisateurs->removeElement($utilisateur)) {
            if (method_exists($utilisateur, 'getServiceMedical') && $utilisateur->getServiceMedical() === $this) {
                if (method_exists($utilisateur, 'setServiceMedical')) {
                    $utilisateur->setServiceMedical(null);
                }
            }
        }

        return $this;
    }

}
