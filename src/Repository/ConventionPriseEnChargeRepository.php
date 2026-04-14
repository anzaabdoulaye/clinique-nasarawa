<?php

namespace App\Repository;

use App\Entity\ConventionPriseEnCharge;
use App\Entity\OrganismePriseEnCharge;
use App\Enum\TypePrestationPEC;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConventionPriseEnChargeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConventionPriseEnCharge::class);
    }

    public function findConventionActive(
        OrganismePriseEnCharge $organisme,
        TypePrestationPEC $type,
        ?\DateTimeInterface $date = null
    ): ?ConventionPriseEnCharge {
        $date ??= new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.organisme = :organisme')
            ->andWhere('c.typePrestation = :type')
            ->andWhere('c.actif = true')
            ->setParameter('organisme', $organisme)
            ->setParameter('type', $type);

        $qb->andWhere('(c.dateDebut IS NULL OR c.dateDebut <= :date)')
            ->andWhere('(c.dateFin IS NULL OR c.dateFin >= :date)')
            ->setParameter('date', $date);

        return $qb->getQuery()->getOneOrNullResult();
    }
}