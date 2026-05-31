<?php

namespace App\DataFixtures;

use App\Entity\Lot;
use App\Entity\Medicament;
use App\Entity\MouvementStock;
use App\Enum\TypeMouvementStock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StockInitialRepriseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1. Dictionnaire de mappage (Mot du Word => Mot exact en Base de données)
        $mapping = [
            'Paracétamol 1g' => 'Paracétamol',
            'Artesun 120mg' => 'Artesun 120',
            'Artesun 60mg' => 'Artesun 60',
            'Sale 500ml' => 'Sérum salé 500ML',
            'Sale 250ml' => 'Sérum salé 250 ML',
            'Analgin' => 'Anagin',
            'Hpv' => 'HPV',
            'GENTA 80 MG' => 'GENTA 80',
            'Ampi 1g' => 'Amp 1g',
            'Omeprazole ' => 'Oméprazole', // L'espace à la fin dans le word
            'Aspegic' => 'Aspégic',
            'Solumedrol 120mg' => 'Solumédrol 120',
            'Solumedrol 1g' => 'Solumédrol 1g',
            'PHENO 100 MG' => 'PHENO 100',
            'Athemeter 80 mg' => 'Atemther 80',
            'NACL 1g' => 'NaCl 1g',
            'KCL 1g' => 'KCl 1g',
            'Ringer 500cc' => 'Ringer 500',
            'Solumedrol 500mg' => 'Solumédrol 500',
            'Poche urinaire' => 'Poche à urine',
            'EOSINE Aqueuse' => 'Eosine',
            'DICLO INJECT' => 'Diclo inj',
            'ADRENALINE' => 'Adrénaline',
            'GENTA 40MG' => 'Genta 40',
            'Vit k1' => 'Vit K1',
            'HALDOL 5mg' => 'Haldol',
            'hudrocortisol' => 'Hydrocortisone',
            'Solumedrol40' => 'Solumédrol 40',
            'synecthene' => 'Synacthène',
            'aciclovir500' => 'Aciclovir 500'
        ];

        // 2. L'intégralité du tableau Word
        $inventaire = [
            ['nom_word' => 'Paracétamol 1g', 'initiale' => 5, 'entree' => 100, 'sortie' => 80],
            ['nom_word' => 'Artesun 120mg', 'initiale' => 0, 'entree' => 50, 'sortie' => 45],
            ['nom_word' => 'Artesun 60mg', 'initiale' => 45, 'entree' => 0, 'sortie' => 25],
            ['nom_word' => 'Ceftri 1g', 'initiale' => 180, 'entree' => 45, 'sortie' => 225],
            ['nom_word' => 'Seringue 10cc', 'initiale' => 500, 'entree' => 0, 'sortie' => 450],
            ['nom_word' => 'Sale 500ml', 'initiale' => 50, 'entree' => 102, 'sortie' => 132],
            ['nom_word' => 'Sale 250ml', 'initiale' => 165, 'entree' => 42, 'sortie' => 207],
            ['nom_word' => 'Glucose 500ml', 'initiale' => 34, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Glucose 250ml', 'initiale' => 89, 'entree' => 0, 'sortie' => 20],
            ['nom_word' => 'Meto', 'initiale' => 8, 'entree' => 10, 'sortie' => 18],
            ['nom_word' => 'Analgin', 'initiale' => 109, 'entree' => 0, 'sortie' => 19],
            ['nom_word' => 'Butyl inj', 'initiale' => 50, 'entree' => 0, 'sortie' => 20],
            ['nom_word' => 'Hpv', 'initiale' => 70, 'entree' => 0, 'sortie' => 70],
            ['nom_word' => 'DEXA', 'initiale' => 60, 'entree' => 10, 'sortie' => 50],
            ['nom_word' => 'GENTA 80 MG', 'initiale' => 0, 'entree' => 70, 'sortie' => 70],
            ['nom_word' => 'Catheter G22', 'initiale' => 50, 'entree' => 50, 'sortie' => 50],
            ['nom_word' => 'Catheter G24', 'initiale' => 0, 'entree' => 50, 'sortie' => 50],
            ['nom_word' => 'Perfuseur', 'initiale' => 275, 'entree' => 0, 'sortie' => 225],
            ['nom_word' => 'Trabar 100', 'initiale' => 0, 'entree' => 20, 'sortie' => 20],
            ['nom_word' => 'Acupan', 'initiale' => 0, 'entree' => 25, 'sortie' => 25],
            ['nom_word' => 'Ampi 1g', 'initiale' => 50, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Omeprazole ', 'initiale' => 10, 'entree' => 70, 'sortie' => 70],
            ['nom_word' => 'Aspegic', 'initiale' => 0, 'entree' => 7, 'sortie' => 7],
            ['nom_word' => 'Solumedrol 120mg', 'initiale' => 5, 'entree' => 7, 'sortie' => 12],
            ['nom_word' => 'Solumedrol 1g', 'initiale' => 16, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Citicoline', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'PHENO 100 MG', 'initiale' => 0, 'entree' => 100, 'sortie' => 50],
            ['nom_word' => 'Tanganil 500', 'initiale' => 5, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Laroxyl 50', 'initiale' => 12, 'entree' => 0, 'sortie' => 12],
            ['nom_word' => 'Largactil 25', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Diazépam', 'initiale' => 0, 'entree' => 134, 'sortie' => 34],
            ['nom_word' => 'Athemeter 80 mg', 'initiale' => 12, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Cipro 200', 'initiale' => 15, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Metro 500', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Furo inj', 'initiale' => 60, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'NACL 1g', 'initiale' => 3, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'KCL 1g', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Loxen ', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Ringer 500cc', 'initiale' => 11, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Solumedrol 500mg', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Tube sec', 'initiale' => 200, 'entree' => 0, 'sortie' => 100],
            ['nom_word' => 'Tube EDTA', 'initiale' => 200, 'entree' => 0, 'sortie' => 100],
            ['nom_word' => 'Seringue 1cc', 'initiale' => 100, 'entree' => 0, 'sortie' => 100],
            ['nom_word' => 'Seringue 5cc', 'initiale' => 400, 'entree' => 0, 'sortie' => 110],
            ['nom_word' => 'Sonde urinaire', 'initiale' => 6, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Poche urinaire', 'initiale' => 3, 'entree' => 0, 'sortie' => 3],
            ['nom_word' => 'Seringue 50 cc', 'initiale' => 45, 'entree' => 0, 'sortie' => 20],
            ['nom_word' => 'Fils a suture', 'initiale' => 9, 'entree' => 0, 'sortie' => 5],
            ['nom_word' => 'VIT B1', 'initiale' => 2, 'entree' => 0, 'sortie' => 2],
            ['nom_word' => 'Nystatine sirop', 'initiale' => 2, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Abaisse langue', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Gants', 'initiale' => 0, 'entree' => 30, 'sortie' => 24],
            ['nom_word' => 'VIT B12', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'MORPHINE', 'initiale' => 10, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'PHENARGAN 25mg', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'COPRESSE STERILLE', 'initiale' => 40, 'entree' => 0, 'sortie' => 40],
            ['nom_word' => 'COMPRESSE 40*40', 'initiale' => 100, 'entree' => 0, 'sortie' => 100],
            ['nom_word' => 'HEMAFER', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'AIGUILLE rachis', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'SPARADRAPS', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'G LUCOSE 30%', 'initiale' => 20, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'EOSINE Aqueuse', 'initiale' => 30, 'entree' => 0, 'sortie' => 20],
            ['nom_word' => 'Salbutamol N ', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'DICLO INJECT', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'LUNETTE', 'initiale' => 11, 'entree' => 0, 'sortie' => 6],
            ['nom_word' => 'COTON', 'initiale' => 2, 'entree' => 0, 'sortie' => 1],
            ['nom_word' => 'GELOFUSINE 500MG', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Sonde d’aspiration (enfant)', 'initiale' => 6, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'ADRENALINE', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Gluconate Ca+', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'LAME bistouris', 'initiale' => 49, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'MASQUE', 'initiale' => 0, 'entree' => 6, 'sortie' => 6],
            ['nom_word' => 'ATROVENT', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'PROKEFEN', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'DAKIN', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'BETADINE', 'initiale' => 2, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'GENTA 40MG', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Vit k1', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'CATHETER G 20', 'initiale' => 1, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'CATHETER G18', 'initiale' => 1, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'HALDOL 5mg', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'hudrocortisol', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Solumedrol40', 'initiale' => 1, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Cimétidine', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Spasfon', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'SULBACEF', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'synecthene', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Lovenox', 'initiale' => 0, 'entree' => 2, 'sortie' => 0],
            ['nom_word' => 'Fleming', 'initiale' => 0, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'aciclovir500', 'initiale' => 30, 'entree' => 35, 'sortie' => 65],
            ['nom_word' => 'SOLUMEDROL 20 MG', 'initiale' => 1, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Sonde vésicalech16 /18', 'initiale' => 12, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'Speculum vaginale', 'initiale' => 30, 'entree' => 0, 'sortie' => 0],
            ['nom_word' => 'prométhazine', 'initiale' => 0, 'entree' => 200, 'sortie' => 0],
        ];

        foreach ($inventaire as $item) {
            $nomWord = $item['nom_word'];
            
            // On vérifie s'il existe une correspondance dans le dictionnaire, sinon on garde le nom du word
            $nomDb = isset($mapping[$nomWord]) ? $mapping[$nomWord] : trim($nomWord);

            // On cherche le médicament en base
            $medicament = $manager->getRepository(Medicament::class)->findOneBy(['nom' => $nomDb]);

            // ==========================================
            // LA MAGIE : CRÉATION AUTO SI INEXISTANT
            // ==========================================
            if (!$medicament) {
                $medicament = new Medicament();
                $medicament->setNom($nomDb);
                // On génère un SKU factice pour passer la validation (ex: MED-A1B2C)
                $medicament->setSku('MED-' . strtoupper(substr(md5($nomDb), 0, 5)));
                $medicament->setCodeBarre('CB' . rand(100000, 999999));
                $medicament->setDescription('Créé automatiquement lors de la bascule d\'inventaire');
                $medicament->setPrixUnitaire(0);
                $medicament->setActif(true);

                $manager->persist($medicament);
                // On flush immédiatement pour pouvoir l'utiliser dans MouvementStock
                $manager->flush(); 
            }

            // Calculs
            $totalEntree = $item['initiale'] + $item['entree']; // (A + B)
            $sortie = $item['sortie']; // (D)

            // Si Q.Initiale = 0 et Entrée = 0, il n'y a rien à faire pour ce médicament (ex: Solumedrol 500mg)
            if ($totalEntree <= 0 && $sortie <= 0) {
                continue; 
            }

            $lot = null;

            // ==========================================
            // ÉTAPE 1 : ENREGISTREMENT DES ENTRÉES
            // ==========================================
            if ($totalEntree > 0) {
                $lot = new Lot();
                $lot->setMedicament($medicament);
                $lot->setNumeroLot('INV-20052026');
                $lot->setQuantite($totalEntree);
                $lot->setDatePeremption(new \DateTime('2027-12-31')); 
                $manager->persist($lot);

                $mvtEntree = new MouvementStock();
                $mvtEntree->setMedicament($medicament);
                $mvtEntree->setLot($lot);
                $mvtEntree->setType(TypeMouvementStock::ENTREE_ACHAT);
                $mvtEntree->setQuantite($totalEntree);
                $mvtEntree->setStockApresMouvement($totalEntree); 
                $mvtEntree->setOperateur('Bascule Système');
                $mvtEntree->setMotif('Reprise inventaire (Initiale + Entrées)');
                $manager->persist($mvtEntree);
            }

            // ==========================================
            // ÉTAPE 2 : ENREGISTREMENT DES SORTIES
            // ==========================================
            if ($sortie > 0 && $lot !== null) {
                $nouveauStockFinal = $lot->getQuantite() - $sortie; // (C - D) = E
                $lot->setQuantite($nouveauStockFinal);

                $mvtSortie = new MouvementStock();
                $mvtSortie->setMedicament($medicament);
                $mvtSortie->setLot($lot);
                $mvtSortie->setType(TypeMouvementStock::SORTIE_SERVICE); 
                $mvtSortie->setQuantite(-$sortie);
                $mvtSortie->setStockApresMouvement($nouveauStockFinal); 
                $mvtSortie->setOperateur('Bascule Système');
                $mvtSortie->setMotif('Reprise inventaire (Sorties)');
                $manager->persist($mvtSortie);
            }
        }

        $manager->flush();
    }
}