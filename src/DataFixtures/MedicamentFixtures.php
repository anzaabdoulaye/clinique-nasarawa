<?php

namespace App\DataFixtures;

use App\Entity\Medicament;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MedicamentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(Medicament::class);

        // Liste enrichie pour correspondre parfaitement au catalogue des tarifs
        $medicaments = [
            'Paracétamol', 'Artesun 120', 'Artesun 60', 'Ceftri 1g', 'Meto', 'Anagin', 'Butyl inj', 'HPV', 'DEXA', 'GENTA 80',
            'Trabar 100', 'Acupan', 'Amp 1g', 'Oméprazole', 'Aspégic', 'Solumédrol 120',
            'Solumédrol 1g', 'Citicoline', 'PHENO 100', 'Tanganil', 'Laroxyl 50',
            'Largactil 25', 'Diazépam', 'Atemther 80', 'Cipro 200', 'Metro 500',
            'Furo inj', 'NaCl 1g', 'KCl 1g', 'Loxen', 'G 10%', 'Ringer 500',
            'Solumédrol 40', 'Cimétidine', 'Spasfon', 'Sulbacef', 'Synacthène',
            'Lovenox', 'Fleming', 'Aciclovir 500', 'Salbutamol', 'Diclo inj',
            'Adrénaline', 'Gluconate Ca+', 'Atrovent', 'Prokefen', 'Dakin',
            'Bétadine', 'Genta 40', 'Vit K1', 'Haldol', 'Hydrocortisone',
            // --- AJOUTS POUR MAPPAGE STRICT ---
            'Catheter', 'Perfuseur', 'Seringue 10CC', 'Sérum salé 500ML',
            'Sérum salé 250 ML', 'PHENO 200', 'Solumédrol 500', 'Gants',
            'Sonde urinaire', 'Poche à urine', 'Somazina 500', 'Somazina 1000',
            'Vit B12', 'Haldol decanoas', 'Eosine'
        ];

        $i = 1;

        foreach ($medicaments as $nom) {
            // Logique d'Upsert : On cherche si le médicament existe déjà
            $medicament = $repository->findOneBy(['nom' => $nom]) ?? new Medicament();

            $medicament->setNom($nom);
            
            // Ne générer le SKU et Code Barre que si c'est une nouvelle entité
            // pour ne pas écraser les codes barres potentiellement scannés en stock
            if (!$medicament->getId()) {
                $medicament->setSku('MED-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT));
                $medicament->setCodeBarre('CB' . rand(100000, 999999));
            }
            
            $medicament->setDescription('Consommable / Médicament : ' . $nom);
            $medicament->setPrixUnitaire(0); // Le prix patient est géré par TarifPrestation
            $medicament->setActif(true);

            $manager->persist($medicament);
            $i++;
        }

        $manager->flush();
    }
}