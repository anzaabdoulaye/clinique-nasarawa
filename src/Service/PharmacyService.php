<?php

namespace App\Service;

use App\Entity\Lot;
use App\Entity\Vente;
use App\Entity\VenteLigne;
use App\Repository\LotRepository;
use Doctrine\ORM\EntityManagerInterface;

class PharmacyService
{
    public function __construct(private EntityManagerInterface $em, private LotRepository $lotRepo)
    {
    }

    /**
     * Décrémente le stock en utilisant la stratégie FIFO par date de péremption (plus proche d'abord).
     * Met à jour les lots concernés et persiste les changements.
     */
    public function decrementStockFromVente(Vente $vente): void
    {
        foreach ($vente->getLignes() as $ligne) {
            $this->decrementStockForLine($ligne);
        }
        $this->em->flush();
    }

    /**
     * Décrémente le stock d'une ligne: si un lot est fourni, on l'utilise;
     * sinon on prélève sur les lots disponibles par date de péremption asc.
     */
    public function decrementStockForLine(VenteLigne $ligne): void
    {
        $qtyToRemove = $ligne->getQuantite();
        $med = $ligne->getMedicament();

        if ($qtyToRemove <= 0) {
            return;
        }

        // Si lot explicitement choisi
        $lot = $ligne->getLot();
        if ($lot) {
            $lot->setQuantite(max(0, $lot->getQuantite() - $qtyToRemove));
            $this->em->persist($lot);
            return;
        }

        // Récupère les lots disponibles pour ce médicament, triés par date de péremption
        $qb = $this->lotRepo->createQueryBuilder('l')
            ->andWhere('l.medicament = :m')
            ->andWhere('l.quantite > 0')
            ->setParameter('m', $med)
            ->orderBy('l.datePeremption', 'ASC')
        ;

        $lots = $qb->getQuery()->getResult();

        foreach ($lots as $l) {
            if ($qtyToRemove <= 0) break;
            $available = $l->getQuantite();
            if ($available <= 0) continue;
            $take = min($available, $qtyToRemove);
            $l->setQuantite($available - $take);
            $qtyToRemove -= $take;
            $this->em->persist($l);
        }

        // Si on ne peut pas satisfaire la totalité, on laisse les quantités à 0 (backorder non géré ici)
    }

    /**
     * Retourne les lots proches de péremption (en jours), utile pour alertes.
     * @param int $days seuil en jours
     * @return Lot[]
     */
    public function getLotsNearExpiration(int $days = 30): array
    {
        $limit = new \DateTimeImmutable(sprintf('+%d days', $days));

        $qb = $this->lotRepo->createQueryBuilder('l')
            ->andWhere('l.datePeremption IS NOT NULL')
            ->andWhere('l.datePeremption <= :limit')
            ->andWhere('l.quantite > 0')
            ->setParameter('limit', $limit)
            ->orderBy('l.datePeremption', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne la quantité totale disponible pour un médicament (somme des lots).
     */
    public function getAvailableQuantity(object $medicament): int
    {
        $qb = $this->lotRepo->createQueryBuilder('l')
            ->select('SUM(l.quantite) as qty')
            ->andWhere('l.medicament = :m')
            ->setParameter('m', $medicament)
        ;

        $res = $qb->getQuery()->getSingleScalarResult();
        return (int) $res;
    }

    public function getMedicamentsLowStock(int $threshold = 10): array
    {
        $medicaments = $this->em->getRepository(\App\Entity\Medicament::class)->findAll();
        $result = [];

        foreach ($medicaments as $medicament) {
            $qty = $this->getAvailableQuantity($medicament);

            if ($qty <= $threshold) {
                $result[] = [
                    'nom' => $medicament->getNom(),
                    'quantite' => $qty,
                    'medicament' => $medicament,
                ];
            }
        }

        usort($result, fn ($a, $b) => $a['quantite'] <=> $b['quantite']);

        return $result;
    }
}
