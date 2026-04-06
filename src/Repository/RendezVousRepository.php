<?php

namespace App\Repository;

use App\Entity\DossierMedical;
use App\Enum\StatutRendezVous;
use App\Entity\RendezVous;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RendezVous>
 */
class RendezVousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RendezVous::class);
    }

    /**
     * @return RendezVous[]
     */
    public function findSelectableForDossierMedical(?DossierMedical $dossierMedical, ?RendezVous $currentRendezVous = null): array
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->leftJoin('r.patient', 'p')
            ->addSelect('p')
            ->orderBy('r.dateHeure', 'ASC');

        if ($dossierMedical !== null) {
            $queryBuilder
                ->andWhere('p = :patient')
                ->setParameter('patient', $dossierMedical->getPatient());
        } else {
            $queryBuilder->andWhere('1 = 0');
        }

        $availabilityExpr = $queryBuilder->expr()->andX(
            'r.dateHeure >= :now',
            $queryBuilder->expr()->notIn('r.statut', ':excludedStatuses')
        );

        if ($currentRendezVous?->getId()) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX(
                    $availabilityExpr,
                    'r.id = :currentRendezVousId'
                ))
                ->setParameter('currentRendezVousId', $currentRendezVous->getId());
        } else {
            $queryBuilder->andWhere($availabilityExpr);
        }

        return $queryBuilder
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('excludedStatuses', [
                StatutRendezVous::ANNULE,
                StatutRendezVous::TERMINE,
            ])
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return RendezVous[] Returns an array of RendezVous objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?RendezVous
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
