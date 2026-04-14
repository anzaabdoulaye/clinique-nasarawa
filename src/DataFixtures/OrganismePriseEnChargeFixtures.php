<?php

namespace App\DataFixtures;

use App\Entity\OrganismePriseEnCharge;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OrganismePriseEnChargeFixtures extends Fixture
{
    public const ORG_CNSS = 'org-cnss';
    public const ORG_NIGELEC = 'org-nigelec';
    public const ORG_SONIDEP = 'org-sonidep';
    public const ORG_CIGNA = 'org-cigna';
    public const ORG_NIGER_TELECOMS = 'org-niger-telecoms';
    public const ORG_ASCOMA = 'org-ascoma';
    public const ORG_SAHAM = 'org-saham';
    public const ORG_ISLAMIC_RELIEF = 'org-islamic-relief';

    public function load(ObjectManager $manager): void
    {
        $organismes = [
            [
                'reference' => self::ORG_CNSS,
                'nom' => 'CNSS',
                'code' => 'CNSS',
                'logo' => null,
                'description' => 'Caisse Nationale de Sécurité Sociale',
            ],
            [
                'reference' => self::ORG_NIGELEC,
                'nom' => 'NIGELEC',
                'code' => 'NIGELEC',
                'logo' => null,
                'description' => 'Société Nigérienne d’Électricité',
            ],
            [
                'reference' => self::ORG_SONIDEP,
                'nom' => 'SONIDEP',
                'code' => 'SONIDEP',
                'logo' => null,
                'description' => 'Société Nigérienne des Produits Pétroliers',
            ],
            [
                'reference' => self::ORG_CIGNA,
                'nom' => 'Cigna',
                'code' => 'CIGNA',
                'logo' => null,
                'description' => 'Assureur santé',
            ],
            [
                'reference' => self::ORG_NIGER_TELECOMS,
                'nom' => 'Niger Telecoms',
                'code' => 'NIGER_TELECOMS',
                'logo' => null,
                'description' => 'Niger Telecoms',
            ],
            [
                'reference' => self::ORG_ASCOMA,
                'nom' => 'ASCOMA',
                'code' => 'ASCOMA',
                'logo' => null,
                'description' => 'Courtier / assurance',
            ],
            [
                'reference' => self::ORG_SAHAM,
                'nom' => 'SAHAM Assurance',
                'code' => 'SAHAM',
                'logo' => null,
                'description' => 'Assurance',
            ],
            [
                'reference' => self::ORG_ISLAMIC_RELIEF,
                'nom' => 'Islamic Relief',
                'code' => 'ISLAMIC_RELIEF',
                'logo' => null,
                'description' => 'Organisation humanitaire',
            ],
        ];

        foreach ($organismes as $data) {
            $organisme = (new OrganismePriseEnCharge())
                ->setNom($data['nom'])
                ->setCode($data['code'])
                ->setLogo($data['logo'])
                ->setDescription($data['description'])
                ->setActif(true);

            $manager->persist($organisme);
            $this->addReference($data['reference'], $organisme);
        }

        $manager->flush();
    }
}