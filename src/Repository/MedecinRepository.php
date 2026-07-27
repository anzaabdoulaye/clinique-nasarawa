<?php

namespace App\Repository;

use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MedecinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medecin::class);
    }

    /**
     * Liste des médecins triés par nom.
     */
    public function findAllOrderByNom(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.nomComplet', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par spécialité.
     */
    public function findBySpecialite(string $specialite): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.specialite = :specialite')
            ->setParameter('specialite', $specialite)
            ->orderBy('m.nomComplet', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom.
     */
    public function search(string $motCle): array
    {
        return $this->createQueryBuilder('m')
            ->where('LOWER(m.nomComplet) LIKE LOWER(:mot)')
            ->setParameter('mot', '%' . $motCle . '%')
            ->orderBy('m.nomComplet', 'ASC')
            ->getQuery()
            ->getResult();
    }
}