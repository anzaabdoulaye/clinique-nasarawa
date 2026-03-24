<?php

namespace App\Controller;

use App\Entity\Lot;
use App\Entity\Vente;
use App\Entity\VenteLigne;
use App\Form\VenteType;
use App\Repository\LotRepository;
use App\Repository\MedicamentRepository;
use App\Repository\VenteRepository;
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
    public function index(VenteRepository $venteRepository): Response
    {
        return $this->render('pharmacie/caisse/index.html.twig', [
            'ventes' => $venteRepository->findBy([], ['date' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_caisse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, LotRepository $lotRepo, PharmacyService $pharmacy): Response
    {
        $vente = new Vente();

        if ($vente->getLignes()->isEmpty()) {
            $vente->addLigne(new VenteLigne());
        }

        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validation côté serveur: disponibilité des quantités et prix
            $hasError = false;
            foreach ($vente->getLignes() as $i => $ligne) {
                $q = $ligne->getQuantite();
                if ($q <= 0) {
                    $form->addError(new FormError(sprintf('Quantité invalide pour la ligne %d.', $i + 1)));
                    $hasError = true;
                    continue;
                }

                $med = $ligne->getMedicament();
                if (!$med) {
                    $form->addError(new FormError(sprintf('Médicament non sélectionné pour la ligne %d.', $i + 1)));
                    $hasError = true;
                    continue;
                }

                // Le prix vient toujours du médicament choisi, jamais de la saisie utilisateur.
                $ligne->setPrixUnitaire((float) $med->getPrixUnitaire());

                // Vérifie si un lot est précisé
                $lot = $ligne->getLot();
                if ($lot) {
                    if ($lot->getQuantite() < $q) {
                        $form->addError(new FormError(sprintf('Le lot %s n\'a pas assez de stock (demande %d, disponible %d).', $lot->getNumeroLot() ?: $lot->getId(), $q, $lot->getQuantite())));
                        $hasError = true;
                        continue;
                    }
                } else {
                    // Vérifie la quantité totale disponible pour ce médicament
                    $available = $pharmacy->getAvailableQuantity($med);
                    if ($available < $q) {
                        $form->addError(new FormError(sprintf('Stock insuffisant pour %s (demande %d, disponible %d).', $med->getNom(), $q, $available)));
                        $hasError = true;
                        continue;
                    }
                }

                // Prix unitaire non négatif
                if ($ligne->getPrixUnitaire() < 0) {
                    $form->addError(new FormError(sprintf('Prix invalide pour la ligne %d.', $i + 1)));
                    $hasError = true;
                }
            }

            if ($hasError) {
                // don't persist, render form with errors
                return $this->render('pharmacie/caisse/new.html.twig', [
                    'form' => $form,
                ]);
            }

            // Utilise PharmacyService pour gérer correctement le prélèvement de stock
            $vente->recalcTotal();
            $em->persist($vente);
            $pharmacy->decrementStockFromVente($vente);
            $em->flush();

            $this->addFlash('success', 'Vente enregistrée.');

            return $this->redirectToRoute('app_caisse_index');
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

            $results[] = [
                'id' => $medicament->getId(),
                'nom' => $medicament->getNom(),
                'codeBarre' => $medicament->getCodeBarre(),
                'sku' => $medicament->getSku(),
                'prixUnitaire' => $medicament->getPrixUnitaire(),
                'quantite' => (int) ($row['stockQty'] ?? 0),
            ];
        }

        return $this->json($results);
    }
}
