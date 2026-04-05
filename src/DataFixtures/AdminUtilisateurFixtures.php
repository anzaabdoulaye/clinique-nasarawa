<?php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use App\Enum\StatutUtilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminUtilisateurFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(Utilisateur::class);

        $admin = $repository->findOneBy(['username' => 'admin']) ?? new Utilisateur();

        $admin
            ->setNom('Administrateur')
            ->setPrenom('Système')
            ->setUsername('admin')
            ->setRoles(['ROLE_ADMIN'])
            ->setStatut(StatutUtilisateur::ACTIF)
            ->setForcePasswordChange(true)
            ->setPassword($this->passwordHasher->hashPassword($admin, 'admin1234'));

        $manager->persist($admin);
        $manager->flush();
    }
}