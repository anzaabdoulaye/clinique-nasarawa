<?php

namespace App\Repository;

use App\Entity\HonoraireMedecin;
use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HonoraireMedecinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HonoraireMedecin::class);
    }

    /**
     * Honoraires d'un médecin.
     */
    public function findByMedecin(Medecin $medecin): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('h.dateActe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Honoraires entre deux dates.
     */
    public function findByPeriode(\DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.dateActe BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('h.dateActe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Total net à payer à un médecin.
     */
    public function totalNetParMedecin(Medecin $medecin): float
    {
        return (float) $this->createQueryBuilder('h')
            ->select('SUM(h.montantNetAPayer)')
            ->where('h.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Total ISB retenu.
     */
    public function totalIsbParMedecin(Medecin $medecin): float
    {
        return (float) $this->createQueryBuilder('h')
            ->select('SUM(h.montantIsb)')
            ->where('h.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Total brut du médecin.
     */
    public function totalBrutParMedecin(Medecin $medecin): float
    {
        return (float) $this->createQueryBuilder('h')
            ->select('SUM(h.montantBrutMedecin)')
            ->where('h.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche par patient.
     */
    public function searchPatient(string $nom): array
    {
        return $this->createQueryBuilder('h')
            ->where('LOWER(h.nomPatient) LIKE LOWER(:nom)')
            ->setParameter('nom', '%' . $nom . '%')
            ->orderBy('h.dateActe', 'DESC')
            ->getQuery()
            ->getResult();
    }
}