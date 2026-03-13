<?php

namespace App\Controller;

use App\Repository\LotRepository;
use App\Repository\MedicamentRepository;
use App\Repository\VenteRepository;
use App\Service\PharmacyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pharmacie')]
final class PharmacieController extends AbstractController
{
    #[Route('/', name: 'app_pharmacie_index', methods: ['GET'])]
    public function index(
        MedicamentRepository $medicamentRepository,
        LotRepository $lotRepository,
        VenteRepository $venteRepository,
        PharmacyService $pharmacyService
    ): Response {
        // Statistiques générales
        $totalMedicaments = $medicamentRepository->count([]);
        $totalLots = $lotRepository->count([]);

        // Lots proches de péremption
        $nearExpirationLots = $pharmacyService->getLotsNearExpiration(30);
        $nearExpirationCount = count($nearExpirationLots);

        // Médicaments à stock faible
        $threshold = 10; // seuil modifiable
        $allMedicaments = $medicamentRepository->findAll();
        $lowStockMedicaments = [];

        foreach ($allMedicaments as $medicament) {
            $qty = $pharmacyService->getAvailableQuantity($medicament);

            if ($qty <= $threshold) {
                $lowStockMedicaments[] = [
                    'id' => $medicament->getId(),
                    'nom' => $medicament->getNom(),
                    'quantite' => $qty,
                ];
            }
        }

        usort($lowStockMedicaments, fn(array $a, array $b) => $a['quantite'] <=> $b['quantite']);
        $lowStockCount = count($lowStockMedicaments);

        // Ventes du jour
        $start = new \DateTimeImmutable('today');
        $end = $start->modify('+1 day');

        $ventesTodayList = $venteRepository->createQueryBuilder('v')
            ->andWhere('v.date >= :start')
            ->andWhere('v.date < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        $ventesToday = count($ventesTodayList);
        $chiffreAffairesToday = 0;

        foreach ($ventesTodayList as $vente) {
            // Adaptez selon votre entité Vente
            if (method_exists($vente, 'getMontantTotal')) {
                $chiffreAffairesToday += (int) $vente->getMontantTotal();
            } elseif (method_exists($vente, 'getTotal')) {
                $chiffreAffairesToday += (int) $vente->getTotal();
            }
        }

        return $this->render('pharmacie/index.html.twig', [
            'totalMedicaments' => $totalMedicaments,
            'totalLots' => $totalLots,
            'lowStockCount' => $lowStockCount,
            'nearExpirationCount' => $nearExpirationCount,
            'ventesToday' => $ventesToday,
            'chiffreAffairesToday' => $chiffreAffairesToday,
            'lowStockMedicaments' => $lowStockMedicaments,
            'nearExpirationLots' => $nearExpirationLots,
        ]);
    }
}