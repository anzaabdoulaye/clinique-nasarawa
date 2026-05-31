<?php

namespace App\Service;

use App\Entity\Medicament;
use App\Entity\MouvementStock;
use App\Enum\TypeMouvementStock;
use Doctrine\ORM\EntityManagerInterface;

class PharmacyManager
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Déduit automatiquement la quantité prescrite du stock en respectant la règle FEFO (First Expired, First Out)
     */
    public function deduireConsommationPatient(Medicament $medicament, int $quantiteDemandee, string $referenceFacture, string $operateur): void
    {
        if ($quantiteDemandee <= 0) return;

        // On récupère tous les lots de ce médicament qui ont du stock, triés par date de péremption la plus proche
        $lots = $this->em->getRepository(\App\Entity\Lot::class)->createQueryBuilder('l')
            ->where('l.medicament = :med')
            ->andWhere('l.quantite > 0')
            ->setParameter('med', $medicament)
            ->orderBy('l.datePeremption', 'ASC') // Règle FEFO vitale
            ->getQuery()
            ->getResult();

        $quantiteRestanteADeduire = $quantiteDemandee;

        foreach ($lots as $lot) {
            if ($quantiteRestanteADeduire <= 0) break;

            $stockDisponibleDansCeLot = $lot->getQuantite();
            
            // On calcule combien on peut prendre dans ce lot
            $quantiteAPrendre = min($stockDisponibleDansCeLot, $quantiteRestanteADeduire);

            // 1. Mise à jour du lot
            $nouveauStock = $stockDisponibleDansCeLot - $quantiteAPrendre; // ⚠️ On calcule
            $lot->setQuantite($nouveauStock);

            // 2. Traçabilité (Mouvement)
            $mvt = new MouvementStock();
            $mvt->setMedicament($medicament);
            $mvt->setLot($lot);
            $mvt->setType(TypeMouvementStock::SORTIE_PATIENT);
            $mvt->setQuantite(-$quantiteAPrendre); // Quantité négative
            // ⚠️ NOUVEAU : On fige le stock final !
            $mvt->setStockApresMouvement($nouveauStock);
            $mvt->setValeurAchatUnitaire($lot->getPrixAchat());
            $mvt->setReferenceDocument($referenceFacture); // Ex: 'FACTURE-1045'
            $mvt->setOperateur($operateur);
            $mvt->setMotif('Consommation automatique via Perception');

            $this->em->persist($mvt);

            // On met à jour ce qu'il reste à déduire pour la boucle suivante (si le lot ne suffisait pas)
            $quantiteRestanteADeduire -= $quantiteAPrendre;
        }

        // Si $quantiteRestanteADeduire > 0 à la fin, cela signifie qu'il y a eu une rupture de stock informatique (ex: on demande 5 perfusions, il n'y en avait que 3 en stock). 
        // Dans une PUI stricte, on enregistre quand même les 3 mouvements, et on log l'anomalie ou on laisse le stock global tomber en négatif.

        $this->em->flush();
    }
}