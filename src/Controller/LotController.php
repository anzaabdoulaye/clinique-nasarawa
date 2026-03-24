<?php

namespace App\Controller;

use App\Entity\Lot;
use App\Entity\Utilisateur;
use App\Enum\MotifMouvement;
use App\Form\LotEditType;
use App\Form\LotReapprovisionnementType;
use App\Form\LotType;
use App\Repository\LotRepository;
use App\Service\ComptabiliteMatiereService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pharmacie/lot')]
final class LotController extends AbstractController
{
    #[Route(name: 'app_lot_index', methods: ['GET'])]
    public function index(LotRepository $lotRepository): Response
    {
        return $this->render('pharmacie/lot/index.html.twig', [
            'lots' => $lotRepository->findBy([], ['datePeremption' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_lot_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        ComptabiliteMatiereService $comptabiliteMatiereService
    ): Response {
        $lot = new Lot();
        $form = $this->createForm(LotType::class, $lot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $this->getUser();
                $utilisateur = $user instanceof \App\Entity\Utilisateur ? $user : null;

                if (!$lot->getMedicament()) {
                    $form->addError(new FormError('Le médicament est obligatoire.'));
                    return $this->render('pharmacie/lot/new.html.twig', [
                        'form' => $form,
                    ]);
                }

                $quantiteInitiale = $lot->getQuantite();
                $prixAchat = $lot->getPrixAchat();

                if ($quantiteInitiale < 0) {
                    $form->addError(new FormError('La quantité initiale ne peut pas être négative.'));
                    return $this->render('pharmacie/lot/new.html.twig', [
                        'form' => $form,
                    ]);
                }

                // La quantité réelle sera alimentée uniquement via la comptabilité matière.
                $lot->setQuantite(0);

                $em->persist($lot);
                $em->flush();

                if ($quantiteInitiale > 0) {
                    $bon = $comptabiliteMatiereService->creerBonEntree(
                        lignesData: [[
                            'medicament' => $lot->getMedicament(),
                            'lot' => $lot,
                            'quantite' => $quantiteInitiale,
                            'prixUnitaire' => $prixAchat,
                            'observation' => 'Entrée automatique depuis la création du lot pharmacie',
                        ]],
                        motif: MotifMouvement::ACHAT,
                        user: $user,
                        reference: 'LOT-' . $lot->getId(),
                        observation: 'Bon d’entrée généré automatiquement depuis la création du lot',
                        ordonnateur: $utilisateur
                    );

                    $comptabiliteMatiereService->validerBon($bon);
                }

                $this->addFlash('success', 'Lot ajouté et traçabilité matière générée avec succès.');

                return $this->redirectToRoute('app_lot_index');
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Erreur lors de l’ajout du lot : ' . $e->getMessage());
            }
        }

        return $this->render('pharmacie/lot/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lot_show', methods: ['GET'])]
    public function show(Lot $lot): Response
    {
        return $this->render('pharmacie/lot/show.html.twig', [
            'lot' => $lot,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lot_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Lot $lot,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(LotEditType::class, $lot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Lot mis à jour.');
            return $this->redirectToRoute('app_lot_index');
        }

        return $this->render('pharmacie/lot/edit.html.twig', [
            'lot' => $lot,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/reapprovisionner', name: 'app_lot_reapprovisionner', methods: ['GET', 'POST'])]
    public function reapprovisionner(
        Request $request,
        Lot $lot,
        ComptabiliteMatiereService $comptabiliteMatiereService
    ): Response {
        $form = $this->createForm(LotReapprovisionnementType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $this->getUser();
                $utilisateur = $user instanceof \App\Entity\Utilisateur ? $user : null;

                $quantite = (int) ($data['quantite'] ?? 0);
                $prixAchat = isset($data['prixAchat']) && $data['prixAchat'] !== null
                    ? (float) $data['prixAchat']
                    : $lot->getPrixAchat();
                $reference = !empty($data['reference']) ? (string) $data['reference'] : null;
                $observation = !empty($data['observation']) ? (string) $data['observation'] : null;

                if ($quantite <= 0) {
                    throw new \RuntimeException('La quantité de réapprovisionnement doit être supérieure à zéro.');
                }

                $bon = $comptabiliteMatiereService->creerBonEntree(
                    lignesData: [[
                        'medicament' => $lot->getMedicament(),
                        'lot' => $lot,
                        'quantite' => $quantite,
                        'prixUnitaire' => $prixAchat,
                        'observation' => 'Réapprovisionnement du lot',
                    ]],
                    motif: MotifMouvement::ACHAT,
                    user: $utilisateur,
                    reference: $reference,
                    observation: $observation ?: 'Bon d’entrée généré depuis le réapprovisionnement du lot',
                    ordonnateur: $utilisateur
                );

                $comptabiliteMatiereService->validerBon($bon);

                $this->addFlash('success', 'Réapprovisionnement effectué et bon d’entrée généré avec succès.');

                return $this->redirectToRoute('app_lot_show', [
                    'id' => $lot->getId(),
                ]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Erreur lors du réapprovisionnement : ' . $e->getMessage());
            }
        }

        return $this->render('pharmacie/lot/reapprovisionner.html.twig', [
            'lot' => $lot,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lot_delete', methods: ['POST'])]
    public function delete(Request $request, Lot $lot, EntityManagerInterface $em): Response
    {
        $token = (string) $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete' . $lot->getId(), $token)) {
            if ($lot->getQuantite() > 0) {
                $this->addFlash('danger', 'Impossible de supprimer un lot qui possède encore du stock.');
                return $this->redirectToRoute('app_lot_index');
            }

            $em->remove($lot);
            $em->flush();
            $this->addFlash('success', 'Lot supprimé.');
        }

        return $this->redirectToRoute('app_lot_index');
    }
}