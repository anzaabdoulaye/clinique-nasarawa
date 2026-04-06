<?php


namespace App\DataFixtures;

use App\Entity\Medicament;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MedicamentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $medicaments = [
            'Paracétamol',
            'Arbun 120',
            'Artisun 60',
            'Ceftri 1g',
            'Glucose 500',
            'Glucose 250',
            'Meto',
            'Anagin',
            'Butyl inj',
            'HPV',
            'DEXA',
            'GENTA 80',
            'Trabar 100',
            'Acupan',
            'Amp 1g',
            'Oméprazole',
            'Aspégic',
            'Solumédrol 120',
            'Solumédrol 1g',
            'Citicoline',
            'PHENO 100',
            'Tanganil',
            'Laroxyl 50',
            'Largactil 25',
            'Diazépam',
            'Atemther 80',
            'Cipro 200',
            'Metro 500',
            'Furo inj',
            'NaCl 1g',
            'KCl 1g',
            'Loxen',
            'G 10%',
            'Ringer 500',
            'Solumédrol 40',
            'Cimétidine',
            'Spasfon',
            'Sulbacef',
            'Synacthène',
            'Lovenox',
            'Fleming',
            'Aciclovir 500',
            'Salbutamol',
            'Diclo inj',
            'Adrénaline',
            'Gluconate Ca+',
            'Atrovent',
            'Prokefen',
            'Dakin',
            'Bétadine',
            'Genta 40',
            'Vit K1',
            'Haldol',
            'Hydrocortisone',
        ];

        $i = 1;

        foreach ($medicaments as $nom) {
            $medicament = new Medicament();

            $medicament->setNom($nom);

            // SKU automatique (ex: MED-0001)
            $medicament->setSku('MED-' . str_pad($i, 4, '0', STR_PAD_LEFT));

            // Code barre simple (optionnel)
            $medicament->setCodeBarre('CB' . rand(100000, 999999));

            // Description vide pour l'instant
            $medicament->setDescription('Médicament ' . $nom);

            // Prix par défaut (tu peux modifier après)
            $medicament->setPrixUnitaire(0);

            $medicament->setActif(true);

            $manager->persist($medicament);

            $i++;
        }

        $manager->flush();
    }
}