<?php

namespace App\Repository;

use App\Entity\AdministrationTraitement;
use App\Entity\TraitementHospitalisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AdministrationTraitementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationTraitement::class);
    }

    public function findOneForTraitementDateHeure(
        TraitementHospitalisation $traitement,
        \DateTimeImmutable $date,
        int $heure
    ): ?AdministrationTraitement {
        return $this->createQueryBuilder('a')
            ->andWhere('a.traitement = :traitement')
            ->andWhere('a.dateAdministration = :date')
            ->andWhere('a.heure = :heure')
            ->setParameter('traitement', $traitement)
            ->setParameter('date', $date)
            ->setParameter('heure', $heure)
            ->getQuery()
            ->getOneOrNullResult();
    }
}