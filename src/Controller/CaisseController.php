<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Vente;
use App\Entity\VenteLigne;
use App\Form\VenteType;
use App\Repository\LotRepository;
use App\Repository\MedicamentRepository;
use App\Repository\VenteRepository;
use App\Service\ComptabiliteMatiereService;
use App\Service\PharmacyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
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
    public function new(
        Request $request,
        EntityManagerInterface $em,
        LotRepository $lotRepo,
        MedicamentRepository $medRepo,
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

                $lot = $ligne->getLot();

                // Lot obligatoire pour la traçabilité matière
                if (!$lot) {
                    $form->addError(new FormError(sprintf(
                        'Le lot est obligatoire pour la ligne %d (%s) afin d’assurer la traçabilité matière.',
                        $i + 1,
                        $med->getNom()
                    )));
                    $hasError = true;
                    continue;
                }

                if ($lot->getMedicament()->getId() !== $med->getId()) {
                    $form->addError(new FormError(sprintf(
                        'Le lot sélectionné pour la ligne %d ne correspond pas au médicament %s.',
                        $i + 1,
                        $med->getNom()
                    )));
                    $hasError = true;
                    continue;
                }

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
                    $form->addError(new FormError(sprintf('Prix invalide pour la ligne %d.', $i + 1)));
                    $hasError = true;
                }
            }

            if ($hasError) {
                return $this->render('pharmacie/caisse/new.html.twig', [
                    'form' => $form,
                ]);
            }

            try {
                $user = $this->getUser();
                $utilisateur = $user instanceof Utilisateur ? $user : null;

                $vente->recalcTotal();
                $em->persist($vente);
                $em->flush();

                // Génère automatiquement le bon matière et décrémente le stock via validation
                $comptabiliteMatiereService->creerEtValiderDepuisVente($vente, $utilisateur);

                $this->addFlash('success', 'Vente enregistrée et bon de sortie définitive généré avec succès.');

                return $this->redirectToRoute('app_caisse_index');
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Erreur lors de l’enregistrement de la vente : ' . $e->getMessage());

                return $this->render('pharmacie/caisse/new.html.twig', [
                    'form' => $form,
                ]);
            }
        }

        return $this->render('pharmacie/caisse/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/medicament/search', name: 'app_caisse_medicament_search', methods: ['GET'])]
    public function medicamentSearch(
        Request $request,
        MedicamentRepository $medRepo,
        LotRepository $lotRepo
    ): Response {
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
                $qty = (int) array_sum(array_map(
                    fn($l) => $l->getQuantite(),
                    $lotRepo->findBy(['medicament' => $m])
                ));

                $results[] = [
                    'id' => $m->getId(),
                    'nom' => $m->getNom(),
                    'prixUnitaire' => $m->getPrixUnitaire(),
                    'quantite' => $qty,
                ];
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