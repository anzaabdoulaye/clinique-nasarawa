<?php

namespace App\Repository;

use App\Entity\Patient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Patient>
 */
class PatientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Patient::class);
    }

    /**
     * @return Patient[]
     */
    public function searchDashboardPatients(string $query, int $limit = 10): array
    {
        $normalizedQuery = trim((string) preg_replace('/\s+/', ' ', $query));

        if ($normalizedQuery == '') {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.dossierMedical', 'dm')
            ->addSelect('dm')
            ->orderBy('p.nom', 'ASC')
            ->addOrderBy('p.prenom', 'ASC')
            ->setMaxResults($limit);

        $terms = array_values(array_filter(explode(' ', mb_strtolower($normalizedQuery))));

        foreach ($terms as $index => $term) {
            $termParam = 'term_' . $index;
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        'LOWER(p.nom) LIKE :' . $termParam,
                        'LOWER(p.prenom) LIKE :' . $termParam,
                        'LOWER(dm.numeroDossier) LIKE :' . $termParam,
                        'p.telephone LIKE :' . $termParam
                    )
                )
                ->setParameter($termParam, '%' . $term . '%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    //    /**
    //     * @return Patient[] Returns an array of Patient objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Patient
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
