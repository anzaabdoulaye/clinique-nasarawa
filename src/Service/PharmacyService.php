<?php

namespace App\Service;

use App\Entity\Lot;
use App\Entity\Medicament;
use App\Entity\Vente;
use App\Entity\VenteLigne;
use App\Repository\LotRepository;
use Doctrine\ORM\EntityManagerInterface;

class PharmacyService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LotRepository $lotRepo
    ) {
    }

    /**
     * Retourne les lots disponibles d'un médicament,
     * triés par date de péremption croissante (FEFO).
     *
     * @return Lot[]
     */
    public function getAvailableLots(Medicament $medicament): array
    {
        return $this->lotRepo->createQueryBuilder('l')
            ->andWhere('l.medicament = :m')
            ->andWhere('l.quantite > 0')
            ->setParameter('m', $medicament)
            ->orderBy('l.datePeremption', 'ASC')
            ->addOrderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne la quantité totale disponible pour un médicament.
     */
    public function getAvailableQuantity(Medicament $medicament): int
    {
        $res = $this->lotRepo->createQueryBuilder('l')
            ->select('COALESCE(SUM(l.quantite), 0)')
            ->andWhere('l.medicament = :m')
            ->setParameter('m', $medicament)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $res;
    }

    /**
     * Vérifie si une ligne de vente est satisfaisable
     * sans modifier le stock.
     */
    public function canSatisfyLine(VenteLigne $ligne): bool
    {
        $quantite = $ligne->getQuantite();
        $medicament = $ligne->getMedicament();

        if ($quantite <= 0 || !$medicament) {
            return false;
        }

        $lot = $ligne->getLot();

        if ($lot) {
            return $lot->getQuantite() >= $quantite;
        }

        return $this->getAvailableQuantity($medicament) >= $quantite;
    }

    /**
     * Vérifie si toute la vente est satisfaisable
     * sans modifier le stock.
     */
    public function canSatisfyVente(Vente $vente): bool
    {
        foreach ($vente->getLignes() as $ligne) {
            if (!$this->canSatisfyLine($ligne)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retourne un plan théorique de prélèvement FEFO
     * sans modifier le stock.
     *
     * Exemple de retour :
     * [
     *   ['lot' => Lot, 'quantite' => 3],
     *   ['lot' => Lot, 'quantite' => 2],
     * ]
     *
     * @return array<int, array{lot: Lot, quantite: int}>
     */
    public function buildFefoAllocationPlan(Medicament $medicament, int $requestedQty): array
    {
        if ($requestedQty <= 0) {
            return [];
        }

        $lots = $this->getAvailableLots($medicament);
        $remaining = $requestedQty;
        $plan = [];

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $available = $lot->getQuantite();
            if ($available <= 0) {
                continue;
            }

            $take = min($available, $remaining);

            $plan[] = [
                'lot' => $lot,
                'quantite' => $take,
            ];

            $remaining -= $take;
        }

        return $remaining > 0 ? [] : $plan;
    }

    /**
     * Retourne les lots proches de péremption.
     *
     * @return Lot[]
     */
    public function getLotsNearExpiration(int $days = 30): array
    {
        $today = new \DateTimeImmutable('today');
        $limit = $today->modify(sprintf('+%d days', $days));

        return $this->lotRepo->createQueryBuilder('l')
            ->andWhere('l.datePeremption IS NOT NULL')
            ->andWhere('l.datePeremption <= :limit')
            ->andWhere('l.quantite > 0')
            ->setParameter('limit', $limit)
            ->orderBy('l.datePeremption', 'ASC')
            ->addOrderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les médicaments dont le stock total est faible.
     *
     * @return array<int, array{nom: string, quantite: int, medicament: Medicament}>
     */
    public function getMedicamentsLowStock(int $threshold = 10): array
    {
        /** @var Medicament[] $medicaments */
        $medicaments = $this->em->getRepository(Medicament::class)->findAll();
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

        usort($result, fn (array $a, array $b) => $a['quantite'] <=> $b['quantite']);

        return $result;
    }
}