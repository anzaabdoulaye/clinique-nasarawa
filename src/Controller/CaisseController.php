<?php

namespace App\Controller;

use App\Entity\Lot;
use App\Entity\Vente;
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
    public function new(Request $request, EntityManagerInterface $em, LotRepository $lotRepo, MedicamentRepository $medRepo, PharmacyService $pharmacy): Response
    {
        $vente = new Vente();
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
    public function medicamentSearch(Request $request, MedicamentRepository $medRepo, LotRepository $lotRepo): Response
    {
        $q = (string) $request->query->get('q', '');
        $results = [];
        if ($q !== '') {
            $meds = $medRepo->createQueryBuilder('m')
                ->andWhere('m.nom LIKE :q OR m.codeBarre = :code')
                ->setParameter('q', '%' . $q . '%')
                ->setParameter('code', $q)
                ->setMaxResults(20)
                ->getQuery()
                ->getResult();

            foreach ($meds as $m) {
                $qty = (int) array_sum(array_map(fn($l) => $l->getQuantite(), $lotRepo->findBy(['medicament' => $m])));
                $results[] = [
                    'id' => $m->getId(),
                    'nom' => $m->getNom(),
                    'prixUnitaire' => $m->getPrixUnitaire(),
                    'quantite' => $qty,
                ];
            }
        }

        return $this->json($results);
    }
}
