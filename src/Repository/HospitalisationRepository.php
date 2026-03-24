<?php

namespace App\Repository;

use App\Entity\Hospitalisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hospitalisation>
 */
class HospitalisationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hospitalisation::class);
    }

    public function findBySearchTerm(?string $term): array
    {
        $qb = $this->createQueryBuilder('h')
            ->leftJoin('h.dossierMedical', 'd')
            ->leftJoin('d.patient', 'p')
            ->addSelect('d', 'p');

        if ($term && trim($term) !== '') {
            $term = mb_strtolower(trim($term));

            $qb->andWhere(
                'LOWER(d.numeroDossier) LIKE :term
                 OR LOWER(p.code) LIKE :term
                 OR LOWER(p.telephone) LIKE :term'
            )
            ->setParameter('term', '%' . $term . '%');
        }

        return $qb->orderBy('h.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}