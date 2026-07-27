<?php

namespace App\Repository;

use App\Entity\JournalCaisse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JournalCaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalCaisse::class);
    }

    /**
     * Journal entre deux dates.
     */
    public function findByPeriode(\DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.dateOperation BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('j.dateOperation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Total des entrées.
     */
    public function totalEntrees(): float
    {
        return (float) $this->createQueryBuilder('j')
            ->select('SUM(j.montant)')
            ->where('j.typeOperation = :type')
            ->setParameter('type', 'ENTREE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Total des sorties.
     */
    public function totalSorties(): float
    {
        return (float) $this->createQueryBuilder('j')
            ->select('SUM(j.montant)')
            ->where('j.typeOperation = :type')
            ->setParameter('type', 'SORTIE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Solde de caisse.
     */
    public function getSolde(): float
    {
        return $this->totalEntrees() - $this->totalSorties();
    }

    /**
     * Recherche par catégorie.
     */
    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.categorie = :categorie')
            ->setParameter('categorie', $categorie)
            ->orderBy('j.dateOperation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}