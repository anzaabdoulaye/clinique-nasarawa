<?php

namespace App\Repository;

use App\Entity\Consultation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consultation>
 */
class ConsultationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consultation::class);
    }

    public function searchByDossierCodeOrTelephone(?string $term): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.dossierMedical', 'dm')
            ->leftJoin('dm.patient', 'p')
            ->leftJoin('c.medecin', 'm')
            ->addSelect('dm', 'p', 'm');

        if ($term && trim($term) !== '') {
            $term = mb_strtolower(trim($term));

            $qb->andWhere(
                'LOWER(dm.numeroDossier) LIKE :term
                 OR LOWER(p.code) LIKE :term
                 OR LOWER(p.telephone) LIKE :term'
            )
            ->setParameter('term', '%' . $term . '%');
        }

        return $qb->orderBy('c.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}