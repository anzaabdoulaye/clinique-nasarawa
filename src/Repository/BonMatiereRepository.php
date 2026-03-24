<?php

namespace App\Repository;

use App\Entity\BonMatiere;
use App\Entity\Vente;
use App\Enum\MotifMouvement;
use App\Enum\TypeBonMatiere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonMatiere>
 */
class BonMatiereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonMatiere::class);
    }

     public function findLastByTypeAndYear(TypeBonMatiere $type, int $year): ?BonMatiere
    {
        $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year));
        $end = new \DateTimeImmutable(sprintf('%d-12-31 23:59:59', $year));

        return $this->createQueryBuilder('b')
            ->andWhere('b.type = :type')
            ->andWhere('b.dateBon BETWEEN :start AND :end')
            ->setParameter('type', $type)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('b.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findVenteBon(Vente $vente): ?BonMatiere
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.vente = :vente')
            ->andWhere('b.type = :type')
            ->andWhere('b.motif = :motif')
            ->setParameter('vente', $vente)
            ->setParameter('type', TypeBonMatiere::SORTIE_DEFINITIVE)
            ->setParameter('motif', MotifMouvement::VENTE)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
}

    //    /**
    //     * @return BonMatiere[] Returns an array of BonMatiere objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?BonMatiere
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
