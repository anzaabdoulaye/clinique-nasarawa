<?php

namespace App\DataFixtures;

use App\Entity\TarifPrestation;
use App\Enum\CategorieTarif;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

class TarifPrestationFixtures extends Fixture
{
    private function resolveServiceExecution(CategorieTarif $categorie): ?string
    {
        return match ($categorie) {
            CategorieTarif::EXAMEN_BIOLOGIQUE,
            CategorieTarif::EXAMEN_FONCTIONNEL => 'laboratoire',

            CategorieTarif::IMAGERIE => 'imagerie',
            CategorieTarif::HOSPITALISATION => 'hospitalisation',

            default => null,
        };
    }

    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();
        $repository = $manager->getRepository(TarifPrestation::class);

        $tarifs = [
            // ================= CONSULTATIONS =================
            ['libelle' => 'Consultation Cardiologie', 'categorie' => CategorieTarif::CONSULTATION, 'prix' => 7500,'prixPriseEnCharge' => 10000],
            ['libelle' => 'Consultation Dermatologique', 'categorie' => CategorieTarif::CONSULTATION, 'prix' => 7500,'prixPriseEnCharge' => 10000],
            ['libelle' => 'Consultation en Urgences toutes spécialités', 'categorie' => CategorieTarif::CONSULTATION, 'prix' => 30000,'prixPriseEnCharge' => 35000],
            ['libelle' => 'Consultation Endocrinologie', 'categorie' => CategorieTarif::CONSULTATION, 'prix' => 7500,'prixPriseEnCharge' => 10000],
            ['libelle' => 'Consultation Gynécologie', 'categorie' => CategorieTarif::CONSULTATION, 'prix' => 5000,'prixPriseEnCharge' => 10000],
            ['libelle' => 'Consultation Médecine Générale', 'categorie' => CategorieTarif::CONSULTATION, 'prix' => 5000,'prixPriseEnCharge' => 5000],
            ['libelle' => 'Consultation Neurologie', 'categorie' => CategorieTarif::CONSULTATION, 'prix' => 7500,'prixPriseEnCharge' => 10000],
            ['libelle' => 'Consultation Pédiatrique', 'categorie' => CategorieTarif::CONSULTATION, 'prix' => 7500,'prixPriseEnCharge' => 10000],

            // ================= BIOLOGIE / LABO =================
            ['libelle' => 'Acide urique', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 3000, 'prixPriseEnCharge' => 4000],
             ['libelle' => 'BNP et PRO BNP', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000, 'prixPriseEnCharge' => 15000],
               ['libelle' => 'Triglycerides', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 5000, 'prixPriseEnCharge' => 5000],
               ['libelle' => 'Troponine', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000, 'prixPriseEnCharge' => 12000],
               ['libelle' => 'TSH', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000, 'prixPriseEnCharge' => 12000],


            ['libelle' => 'Albumine + Sucre', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 2000, 'prixPriseEnCharge' => 2500],
            ['libelle' => 'Antigène HBs', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 4000, 'prixPriseEnCharge' => 5000],
            ['libelle' => 'ASLO', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 3500, 'prixPriseEnCharge' => 4000],
            ['libelle' => 'Bandelette urinaire', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 2500, 'prixPriseEnCharge' => 5000],
            ['libelle' => 'Beta HCG plasmatique', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 4000, 'prixPriseEnCharge' => 5000],
            ['libelle' => 'Beta HCG urinaire', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 2000, 'prixPriseEnCharge' => 3000],
            ['libelle' => 'Bilirubine directe', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 4500, 'prixPriseEnCharge' => 4500],
            ['libelle' => 'Bilirubine indirecte', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 4500, 'prixPriseEnCharge' => 4500],
            ['libelle' => 'Bilirubine totale', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 3500, 'prixPriseEnCharge' => 3500],
            ['libelle' => 'BW (Sérologie syphilitique)', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 4000, 'prixPriseEnCharge' => 5000],
            ['libelle' => 'C peptidémie', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000, 'prixPriseEnCharge' => 10000],
            ['libelle' => 'Calcémie', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 4000, 'prixPriseEnCharge' => 5000],
            ['libelle' => 'Cholestérol HDL', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 5000, 'prixPriseEnCharge' => 6000],
             ['libelle' => 'Hemoglobine gluquée', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000, 'prixPriseEnCharge' => 12000],
            ['libelle' => 'Cholestérol LDL', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 5000, 'prixPriseEnCharge' => 6000],
            ['libelle' => 'Cholestérol total', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 5000, 'prixPriseEnCharge' => 6000],
            ['libelle' => 'CRP quantitative', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 8000,'prixPriseEnCharge' => 9000],
            ['libelle' => 'Culot urinaire', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 2000,'prixPriseEnCharge' => 3000],
            ['libelle' => 'D DIMÈRES', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 12000],
            ['libelle' => 'Dosage Vitamine B12', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 12000],
            ['libelle' => 'ECBU', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 12000],
            ['libelle' => 'Facteur rhumatoïde', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 4500,'prixPriseEnCharge' => 4500],
            ['libelle' => 'Fer sérique', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 4500,'prixPriseEnCharge' => 4500],
            ['libelle' => 'Ferritinémie', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 12000],
            ['libelle' => 'FSH', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 12000],
            ['libelle' => 'Gamma GT', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 5000,'prixPriseEnCharge' => 30000],
            ['libelle' => 'Glycémie capillaire', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 1000,'prixPriseEnCharge' => 1500],
            ['libelle' => 'Glycémie veineuse', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 1500,'prixPriseEnCharge' => 5000],
            ['libelle' => 'Goutte épaisse + densité parasitaire', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 3000,'prixPriseEnCharge' => 4000],
            ['libelle' => 'Groupage sanguin rhésus', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 2500,'prixPriseEnCharge' => 3000],
            ['libelle' => 'Hémoculture', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 15000,'prixPriseEnCharge' => 15000],
            ['libelle' => 'HIV', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 4000,'prixPriseEnCharge' => 5000],
            ['libelle' => 'IgE', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 10000],
            ['libelle' => 'Ionogramme sanguin', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 8000,'prixPriseEnCharge' => 10000],
            ['libelle' => 'LH', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 10000],
            ['libelle' => 'Magnésémie', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 4000,'prixPriseEnCharge' => 5000],
            ['libelle' => 'Micro albuminémie', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 10000],
            ['libelle' => 'NFS', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 4500,'prixPriseEnCharge' => 5000],
            ['libelle' => 'Œstradiol', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 10000],
            ['libelle' => 'Progestérone', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 12000],
            ['libelle' => 'Protéinurie de 24h', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 5000,'prixPriseEnCharge' => 5000],
            ['libelle' => 'Protides totaux', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 5000,'prixPriseEnCharge' => 5000],
            ['libelle' => 'PSA', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 12000],
            ['libelle' => 'Selle KOPA', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 3000,'prixPriseEnCharge' => 3500],
            ['libelle' => 'T3', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 12000],
            ['libelle' => 'T4 Libre', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 12000],
            ['libelle' => 'Taux de prothrombine', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 6000,'prixPriseEnCharge' => 8000],
            ['libelle' => 'Taux de réticulocytes', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 3500,'prixPriseEnCharge' => 3500],
            ['libelle' => 'TCA', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 10000],
            ['libelle' => 'Temps de saignement', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 1500,'prixPriseEnCharge' => 1500],
            ['libelle' => 'Testostérone', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 10000,'prixPriseEnCharge' => 12000],
            ['libelle' => 'Transaminase', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 6000,'prixPriseEnCharge' => 8000],
            ['libelle' => 'Urée', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 2000,'prixPriseEnCharge' => 4000],
            ['libelle' => 'Vitesse de sédimentation (VS)', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 3000,'prixPriseEnCharge' => 3000],
            ['libelle' => 'Widal', 'categorie' => CategorieTarif::EXAMEN_BIOLOGIQUE, 'prix' => 3000,'prixPriseEnCharge' => 4000],

            // ================= EXAMENS FONCTIONNELS =================
            ['libelle' => 'ECG + Interprétation', 'categorie' => CategorieTarif::EXAMEN_FONCTIONNEL, 'prix' => 10000,'prixPriseEnCharge' => 15000],
            ['libelle' => 'Electrocardiogramme (ECG)', 'categorie' => CategorieTarif::EXAMEN_FONCTIONNEL, 'prix' => 5000,'prixPriseEnCharge' => 7500],
            ['libelle' => 'EEG (veille + sommeil)', 'categorie' => CategorieTarif::EXAMEN_FONCTIONNEL, 'prix' => 25000,'prixPriseEnCharge' => 30000],
            ['libelle' => 'Holter ECG', 'categorie' => CategorieTarif::EXAMEN_FONCTIONNEL, 'prix' => 50000,'prixPriseEnCharge' => 60000],
            ['libelle' => 'MAPA', 'categorie' => CategorieTarif::EXAMEN_FONCTIONNEL, 'prix' => 50000,'prixPriseEnCharge' => 60000],

            // ================= IMAGERIE / ECHOGRAPHIE =================
            ['libelle' => 'Echographie abdomino-pelvienne', 'categorie' => CategorieTarif::IMAGERIE, 'prix' => 15000,'prixPriseEnCharge' => 20000],
            ['libelle' => 'Echographie doppler artérielle d’un membre', 'categorie' => CategorieTarif::IMAGERIE, 'prix' => 25000,'prixPriseEnCharge' => 25000],
            ['libelle' => 'Echographie doppler cardiaque', 'categorie' => CategorieTarif::IMAGERIE, 'prix' => 30000,'prixPriseEnCharge' => 35000],
            ['libelle' => 'Echographie doppler des TSA', 'categorie' => CategorieTarif::IMAGERIE, 'prix' => 25000,'prixPriseEnCharge' => 35000],
            ['libelle' => 'Echographie doppler veineux d’un membre', 'categorie' => CategorieTarif::IMAGERIE, 'prix' => 25000,'prixPriseEnCharge' => 35000],
            ['libelle' => 'Echographie pelvienne', 'categorie' => CategorieTarif::IMAGERIE, 'prix' => 4000,'prixPriseEnCharge' => 7500],

            // ================= HOSPITALISATION =================
            ['libelle' => 'Caution hospitalisation', 'categorie' => CategorieTarif::HOSPITALISATION, 'prix' => 100000,'prixPriseEnCharge' => 100000],
            ['libelle' => 'Hospitalisation chambre 1 lit', 'categorie' => CategorieTarif::HOSPITALISATION, 'prix' => 20000, 'prixPriseEnCharge' => 30000],
            ['libelle' => 'Hospitalisation Salle G 1 lit', 'categorie' => CategorieTarif::HOSPITALISATION, 'prix' => 10000, 'prixPriseEnCharge' => 20000],
            ['libelle' => 'Hospitalisation / jour : 1ère catégorie', 'categorie' => CategorieTarif::HOSPITALISATION, 'prix' => 30000,'prixPriseEnCharge' => 40000],
            ['libelle' => 'Hospitalisation / jour : 2ème catégorie', 'categorie' => CategorieTarif::HOSPITALISATION, 'prix' => 20000,'prixPriseEnCharge' => 30000],
            ['libelle' => 'Hospitalisation / jour : salle commune à 2 lits', 'categorie' => CategorieTarif::HOSPITALISATION, 'prix' => 10000,'prixPriseEnCharge' => 20000],
            ['libelle' => 'Mise en observation', 'categorie' => CategorieTarif::HOSPITALISATION, 'prix' => 10000,'prixPriseEnCharge' => 10000],
            ['libelle' => 'Mise en observation moins de 6 heures', 'categorie' => CategorieTarif::HOSPITALISATION, 'prix' => 5000,'prixPriseEnCharge' => 15000],

            // ================= ACTES / SOINS / CONSOMMABLES =================
            ['libelle' => 'Concentrateur d’oxygène / heure', 'categorie' => CategorieTarif::CONSOMMABLE, 'prix' => 2000,'prixPriseEnCharge' => 3000],
            ['libelle' => 'Expertise médicale', 'categorie' => CategorieTarif::ACTE, 'prix' => 50000,'prixPriseEnCharge' => 50000],
            ['libelle' => 'Infiltration', 'categorie' => CategorieTarif::ACTE, 'prix' => 10000,'prixPriseEnCharge' => 15000],
            ['libelle' => 'Kinésithérapie fonctionnelle', 'categorie' => CategorieTarif::ACTE, 'prix' => 7500,'prixPriseEnCharge' => 10000],
            ['libelle' => 'Nébulisation', 'categorie' => CategorieTarif::ACTE, 'prix' => 10000,'prixPriseEnCharge' => 12000],
            ['libelle' => 'Oxygène pur / heure', 'categorie' => CategorieTarif::CONSOMMABLE, 'prix' => 10000,'prixPriseEnCharge' => 20000],
            ['libelle' => 'Pousse seringue électrique', 'categorie' => CategorieTarif::CONSOMMABLE, 'prix' => 10000, 'prixPriseEnCharge' => 12000], // image ambiguë: "10000/24h" possible
        ];

        

        foreach ($tarifs as $item) {
            $code = strtoupper((string) $slugger->slug($item['libelle']));

            $tarif = $repository->findOneBy(['code' => $code])
                ?? $repository->findOneBy(['libelle' => $item['libelle']])
                ?? new TarifPrestation();

            $tarif->setLibelle($item['libelle']);
            $tarif->setCategorie($item['categorie']);
            $tarif->setPrix($item['prix']);
            $tarif->setPrixPriseEnCharge($item['prixPriseEnCharge'] ?? null);
            $tarif->setActif(true);
            $tarif->setCode($code);
            $tarif->setServiceExecution(
                $item['serviceExecution'] ?? $this->resolveServiceExecution($item['categorie'])
            );
            $manager->persist($tarif);
        }

        $manager->flush();
    }
}