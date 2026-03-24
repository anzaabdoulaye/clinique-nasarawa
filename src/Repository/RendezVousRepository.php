<?php

namespace App\Repository;

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

    public function findBySearchTerm(?string $term): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.patient', 'p')
            ->leftJoin('p.dossierMedical', 'd')
            ->addSelect('p', 'd');

        if ($term && trim($term) !== '') {
            $term = mb_strtolower(trim($term));

            $qb->andWhere(
                'LOWER(p.code) LIKE :term
                 OR LOWER(p.telephone) LIKE :term
                 OR LOWER(d.numeroDossier) LIKE :term'
            )
            ->setParameter('term', '%' . $term . '%');
        }

        return $qb->orderBy('r.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}