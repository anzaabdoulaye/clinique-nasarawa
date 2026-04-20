<?php

namespace App\Controller;

use App\Entity\Lot;
use App\Entity\Vente;
use App\Entity\VenteLigne;
use App\Form\VenteType;
use App\Repository\LotRepository;
use App\Repository\MedicamentRepository;
use App\Repository\VenteRepository;
use App\Service\ComptabiliteMatiereService;
use App\Service\PharmacyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse')]
final class CaisseController extends AbstractController
{
    #[Route(name: 'app_caisse_index', methods: ['GET'])]
    public function index(Request $request, VenteRepository $venteRepository): Response
    {
        // Récupérer le filtre de période depuis les paramètres de requête
        $periodeFilter = $request->query->get('periode');
        if ($periodeFilter === null) {
            // Par défaut, filtrer sur les ventes des 30 derniers jours
            $periodeFilter = 'recent';
        }

        if ($periodeFilter === 'recent') {
            $date = new \DateTimeImmutable('-30 days');
            $ventes = $venteRepository->createQueryBuilder('v')
                ->andWhere('v.date >= :date')
                ->setParameter('date', $date)
                ->orderBy('v.date', 'DESC')
                ->getQuery()
                ->getResult();
        } elseif ($periodeFilter === 'month') {
            $now = new \DateTimeImmutable();
            $startOfMonth = $now->setDate($now->format('Y'), $now->format('m'), 1)->setTime(0, 0, 0);
            $ventes = $venteRepository->createQueryBuilder('v')
                ->andWhere('v.date >= :date')
                ->setParameter('date', $startOfMonth)
                ->orderBy('v.date', 'DESC')
                ->getQuery()
                ->getResult();
        } elseif ($periodeFilter === 'quarter') {
            $date = new \DateTimeImmutable('-90 days');
            $ventes = $venteRepository->createQueryBuilder('v')
                ->andWhere('v.date >= :date')
                ->setParameter('date', $date)
                ->orderBy('v.date', 'DESC')
                ->getQuery()
                ->getResult();
        } elseif ($periodeFilter === 'year') {
            $date = new \DateTimeImmutable('-365 days');
            $ventes = $venteRepository->createQueryBuilder('v')
                ->andWhere('v.date >= :date')
                ->setParameter('date', $date)
                ->orderBy('v.date', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            $ventes = $venteRepository->findBy([], ['date' => 'DESC']);
        }

        return $this->render('pharmacie/caisse/index.html.twig', [
            'ventes' => $ventes,
            'periodeFilter' => $periodeFilter,
        ]);
    }

    #[Route('/new', name: 'app_caisse_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    EntityManagerInterface $em,
    LotRepository $lotRepo,
    PharmacyService $pharmacy,
    ComptabiliteMatiereService $comptabiliteMatiereService
): Response {
    $vente = new Vente();

    if ($vente->getLignes()->isEmpty()) {
        $vente->addLigne(new VenteLigne());
    }

    $form = $this->createForm(VenteType::class, $vente);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $hasError = false;

        foreach ($vente->getLignes() as $i => $ligne) {
            $q = $ligne->getQuantite();

            if ($q <= 0) {
                $form->addError(new FormError(sprintf(
                    'Quantité invalide pour la ligne %d.',
                    $i + 1
                )));
                $hasError = true;
                continue;
            }

            $med = $ligne->getMedicament();

            if (!$med) {
                $form->addError(new FormError(sprintf(
                    'Médicament non sélectionné pour la ligne %d.',
                    $i + 1
                )));
                $hasError = true;
                continue;
            }

            $lot = $ligne->getLot();

            // Si aucun lot n'est encore renseigné, on tente une auto-affectation
            if (!$lot) {
                $lotsDisponibles = $pharmacy->getAvailableLots($med);

                if (count($lotsDisponibles) === 0) {
                    $form->addError(new FormError(sprintf(
                        'Aucun lot disponible pour %s.',
                        $med->getNom()
                    )));
                    $hasError = true;
                    continue;
                }

                if (count($lotsDisponibles) === 1) {
                    $lot = $lotsDisponibles[0];
                    $ligne->setLot($lot);
                } else {
                    $form->addError(new FormError(sprintf(
                        'Plusieurs lots sont disponibles pour %s. Veuillez choisir explicitement un lot.',
                        $med->getNom()
                    )));
                    $hasError = true;
                    continue;
                }
            }

            // Vérifie cohérence médicament / lot
            if ($lot->getMedicament()->getId() !== $med->getId()) {
                $form->addError(new FormError(sprintf(
                    'Le lot sélectionné pour la ligne %d ne correspond pas au médicament %s.',
                    $i + 1,
                    $med->getNom()
                )));
                $hasError = true;
                continue;
            }

            // Vérifie stock sur le lot choisi
            if ($lot->getQuantite() < $q) {
                $form->addError(new FormError(sprintf(
                    'Le lot %s n\'a pas assez de stock pour %s (demande %d, disponible %d).',
                    $lot->getNumeroLot() ?: $lot->getId(),
                    $med->getNom(),
                    $q,
                    $lot->getQuantite()
                )));
                $hasError = true;
                continue;
            }

            if ($ligne->getPrixUnitaire() < 0) {
                $form->addError(new FormError(sprintf(
                    'Prix invalide pour la ligne %d.',
                    $i + 1
                )));
                $hasError = true;
            }
        }

        if ($hasError) {
            return $this->render('pharmacie/caisse/new.html.twig', [
                'form' => $form,
            ]);
        }

        try {
            $currentUser = $this->getUser();
            $utilisateur = $currentUser instanceof \App\Entity\Utilisateur ? $currentUser : null;

            $vente->setVendeur($utilisateur);
            $vente->recalcTotal();

            $em->persist($vente);
            $em->flush();

            // Source unique d’écriture stock : comptabilité matière
            $comptabiliteMatiereService->creerEtValiderDepuisVente($vente, $utilisateur);

            $this->addFlash('success', 'Vente enregistrée et bon de sortie définitive généré avec succès.');

            return $this->redirectToRoute('app_caisse_index');
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Erreur lors de l’enregistrement de la vente : ' . $e->getMessage());
        }
    }

    return $this->render('pharmacie/caisse/new.html.twig', [
        'form' => $form,
    ]);
}

    #[Route('/medicament/search', name: 'app_caisse_medicament_search', methods: ['GET'])]
    public function medicamentSearch(Request $request, MedicamentRepository $medRepo): Response
    {
        $q = (string) $request->query->get('q', '');
        $results = [];

        $qb = $medRepo->createQueryBuilder('m')
            ->leftJoin('m.lots', 'l')
            ->addSelect('COALESCE(SUM(l.quantite), 0) AS stockQty')
            ->andWhere('m.actif = :active')
            ->setParameter('active', true)
            ->groupBy('m.id')
            ->setMaxResults(20);

        if ($q !== '') {
            $qb
                ->andWhere('m.nom LIKE :term OR m.codeBarre LIKE :term OR m.sku LIKE :term')
                ->addSelect('CASE WHEN m.codeBarre = :exactCode THEN 0 WHEN m.nom LIKE :prefix THEN 1 WHEN m.codeBarre LIKE :prefix THEN 2 ELSE 3 END AS HIDDEN relevanceRank')
                ->setParameter('term', '%' . $q . '%')
                ->setParameter('exactCode', $q)
                ->setParameter('prefix', $q . '%')
                ->orderBy('relevanceRank', 'ASC')
                ->addOrderBy('stockQty', 'DESC')
                ->addOrderBy('m.nom', 'ASC');
        } else {
            $qb
                ->orderBy('stockQty', 'DESC')
                ->addOrderBy('m.nom', 'ASC');
        }

        foreach ($qb->getQuery()->getResult() as $row) {
            $medicament = $row[0] ?? null;
            if (!$medicament) {
                continue;
            }

            $lots = [];
            foreach ($medicament->getLots() as $lot) {
                if ($lot->getQuantite() <= 0) {
                    continue;
                }
                $lots[] = [
                    'id' => $lot->getId(),
                    'numeroLot' => $lot->getNumeroLot() ?: 'Lot #' . $lot->getId(),
                    'datePeremption' => $lot->getDatePeremption() ? $lot->getDatePeremption()->format('d/m/Y') : null,
                    'quantite' => $lot->getQuantite(),
                ];
            }

            $results[] = [
                'id' => $medicament->getId(),
                'nom' => $medicament->getNom(),
                'codeBarre' => $medicament->getCodeBarre(),
                'sku' => $medicament->getSku(),
                'prixUnitaire' => $medicament->getPrixUnitaire(),
                'quantite' => (int) ($row['stockQty'] ?? 0),
                'lots' => $lots,
            ];
        }

        return $this->json($results);
    }
}
