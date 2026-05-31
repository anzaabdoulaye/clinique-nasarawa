<?php

namespace App\DataFixtures;

use App\Entity\Antibiotique;
use App\Entity\Germe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LaboratoireFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1. INSERTION DES GERMES COURANTS
        $germes = [
            'Escherichia coli',
            'Staphylococcus aureus',
            'Klebsiella pneumoniae',
            'Pseudomonas aeruginosa',
            'Streptococcus agalactiae (Groupe B)',
            'Enterococcus faecalis',
            'Proteus mirabilis',
            'Salmonella spp.',
            'Shigella spp.',
            'Candida albicans' // Levure souvent testée
        ];

        foreach ($germes as $nomGerme) {
            $germe = new Germe();
            $germe->setNom($nomGerme);
            $germe->setEstActif(true);
            $manager->persist($germe);
        }

        // 2. INSERTION DES ANTIBIOTIQUES (Classés par familles selon votre PDF)
        $famillesAntibiotiques = [
            'Pénicillines' => [
                'Peni-G', 'Ampicilline', 'Amoxicilline', 'Amoxi + A Clavulanique', 
                'Ticarcilline', 'Piperacilline', 'Pipera + Tazobactam', 'Oxacilline'
            ],
            'Aminosides' => [
                'Kanamycine', 'Gentamycine', 'Amikacine', 'Tabramycine'
            ],
            'Tétracyclines' => [
                'Tétracycline', 'Tetracycline B', 'Doxycicline'
            ],
            'Macrolides' => [
                'Macrolides', 'Erythromycine', 'Azithromycine'
            ],
            'Quinolones' => [
                'Quinolones', 'Ac nalidixique', 'Pefloxacine', 'Ofloxacine', 
                'Norfloxacine', 'Ciprofloxacine', 'Levofloxacine'
            ],
            'Céphalosporines' => [
                'Céphamycine', 'cefoxitine', 'Céphalosporines', 'cefixime', 
                'cefepime', 'cefalotine', 'cefuroxime', 'cefotaxime', 'ceftriaxone'
            ],
            'Polypeptides' => [
                'Colistine'
            ],
            'Sulfamides' => [
                'Sulfamides', 'Cotrimoxazole'
            ],
            'Phénicolés' => [
                'Phenicolés', 'Chloramphenicol'
            ],
            'Carbapénèmes' => [
                'Carbapénemes', 'Imipenème', 'Meropeneme'
            ],
            'Autres' => [
                'Furanes', 'Tinidazole', 'Ac fusidique', 'Metronidazole', 
                'Fosfomycine', 'Vancomycine', 'Rifampicine', 'Lincomycine', 
                'Teicomycine', 'Nitroxoline'
            ]
        ];

        foreach ($famillesAntibiotiques as $famille => $listeAtb) {
            foreach ($listeAtb as $nomAtb) {
                $antibiotique = new Antibiotique();
                $antibiotique->setNom($nomAtb);
                $antibiotique->setFamille($famille);
                $antibiotique->setEstActif(true);
                $manager->persist($antibiotique);
            }
        }

        // Sauvegarde en base de données
        $manager->flush();
    }
}