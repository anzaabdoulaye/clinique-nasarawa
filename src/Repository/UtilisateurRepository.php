<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * @return list<Utilisateur>
     */
    public function findDoctors(): array
    {
        $utilisateurs = $this->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']);

        return array_values(array_filter(
            $utilisateurs,
            static fn (Utilisateur $utilisateur) => in_array('ROLE_MEDECIN', $utilisateur->getRoles(), true)
        ));
    }

    /**
     * @param list<string> $roles
     *
     * @return list<Utilisateur>
     */
    public function findUsersByRoles(array $roles): array
    {
        $utilisateurs = $this->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']);

        return array_values(array_filter(
            $utilisateurs,
            static fn (Utilisateur $utilisateur) => array_intersect($roles, $utilisateur->getRoles()) !== []
        ));
    }

    //    /**
    //     * @return Utilisateur[] Returns an array of Utilisateur objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Utilisateur
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
