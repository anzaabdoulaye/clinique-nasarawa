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

    public function searchByDossierOrPatientCode(string $term): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.dossierMedical', 'dm')
            ->leftJoin('dm.patient', 'p')
            ->addSelect('dm', 'p', 'm')
            ->leftJoin('c.medecin', 'm')
            ->andWhere('dm.numeroDossier LIKE :term OR p.code LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Consultation[] Returns an array of Consultation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

        return $qb->orderBy('c.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function searchVisibleForUser(?string $search, \App\Entity\Utilisateur $user): array
{
    $qb = $this->createQueryBuilder('c')
        ->leftJoin('c.dossierMedical', 'dm')
        ->leftJoin('dm.patient', 'p')
        ->leftJoin('c.medecin', 'm')
        ->addSelect('dm', 'p', 'm')
        ->orderBy('c.id', 'DESC');

    if ($search) {
        $qb->andWhere('dm.codeDossier LIKE :search OR p.telephone LIKE :search')
           ->setParameter('search', '%' . trim($search) . '%');
    }

    $roles = $user->getRoles();

    $isAdmin = in_array('ROLE_ADMIN', $roles, true);
    $isAccueil = in_array('ROLE_ACCUEIL', $roles, true);
    $isInfirmier = in_array('ROLE_INFIRMIER', $roles, true);
    $isMedecin = in_array('ROLE_MEDECIN', $roles, true);

    // Admin / Accueil / Infirmier voient tout
    if ($isAdmin || $isAccueil || $isInfirmier) {
        return $qb->getQuery()->getResult();
    }

    // Médecin : uniquement ses consultations
    if ($isMedecin) {
        $qb->andWhere('c.medecin = :medecin')
           ->setParameter('medecin', $user);

        return $qb->getQuery()->getResult();
    }

    // Par sécurité : aucun accès si rôle non prévu
    $qb->andWhere('1 = 0');

    return $qb->getQuery()->getResult();
}
}
