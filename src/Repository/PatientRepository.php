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

   public function findBySearchTerm(?string $term): array
{
    $qb = $this->createQueryBuilder('p')
        ->leftJoin('p.dossierMedical', 'd')
        ->addSelect('d');

    if ($term && trim($term) !== '') {
        $term = mb_strtolower(trim($term));

        $qb->where('LOWER(p.code) LIKE :term')
           ->orWhere('LOWER(p.telephone) LIKE :term')
           ->orWhere('LOWER(d.numeroDossier) LIKE :term')
           ->setParameter('term', '%' . $term . '%');
    }

    return $qb->orderBy('p.id', 'DESC')
              ->getQuery()
              ->getResult();
}
}