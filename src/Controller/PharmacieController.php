<?php

namespace App\Controller;

use App\Enum\TypeMouvementStock;
use App\Repository\LotRepository;
use App\Repository\MedicamentRepository;
use App\Repository\MouvementStockRepository;
use App\Service\PharmacyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/pharmacie')]
final class PharmacieController extends AbstractController
{
    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_PHARMACIE')"))]
    #[Route('/', name: 'app_pharmacie_index', methods: ['GET'])]
    public function index(
        MedicamentRepository $medicamentRepository,
        LotRepository $lotRepository,
        MouvementStockRepository $mouvementStockRepository, // 👈 Nouveau Repository
        PharmacyService $pharmacyService
    ): Response {
        // Statistiques générales
        $totalMedicaments = $medicamentRepository->count([]);
        $totalLots = $lotRepository->count([]);

        // Lots proches de péremption
        $nearExpirationLots = $pharmacyService->getLotsNearExpiration(30);
        $nearExpirationCount = count($nearExpirationLots);

        // Médicaments à stock faible
        $threshold = 10; 
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

        // 👈 NOUVEAU : Consommations (Sorties) du jour au lieu des ventes
        $start = new \DateTimeImmutable('today');
        $end = $start->modify('+1 day');

        $mouvementsTodayList = $mouvementStockRepository->createQueryBuilder('m')
            ->andWhere('m.createdAt >= :start')
            ->andWhere('m.createdAt < :end')
            // On ne compte que les sorties pour l'activité du jour
            ->andWhere('m.type IN (:typesSortie)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('typesSortie', [
                TypeMouvementStock::SORTIE_PATIENT,
                TypeMouvementStock::SORTIE_SERVICE,
                TypeMouvementStock::SORTIE_PERTE
            ])
            ->getQuery()
            ->getResult();

        $sortiesTodayCount = count($mouvementsTodayList);
        $valeurSortiesToday = 0;

        foreach ($mouvementsTodayList as $mvt) {
            // La comptabilité matière raisonne en coût d'achat (Combien d'argent en stock on a consommé aujourd'hui ?)
            // On utilise abs() car la quantité d'une sortie est stockée en négatif
            $valeurAchat = $mvt->getValeurAchatUnitaire() ?? 0;
            $valeurSortiesToday += abs($mvt->getQuantite()) * $valeurAchat;
        }

        return $this->render('pharmacie/index.html.twig', [
            'totalMedicaments' => $totalMedicaments,
            'totalLots' => $totalLots,
            'lowStockCount' => $lowStockCount,
            'nearExpirationCount' => $nearExpirationCount,
            'sortiesTodayCount' => $sortiesTodayCount, // Remplace ventesToday
            'valeurSortiesToday' => $valeurSortiesToday, // Remplace chiffreAffairesToday
            'lowStockMedicaments' => $lowStockMedicaments,
            'nearExpirationLots' => $nearExpirationLots,
        ]);
    }
}