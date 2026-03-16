<?php

namespace App\Controller;

use App\Entity\Facture;
use App\Form\EncaissementType;
use App\Repository\FactureRepository;
use App\Service\FacturationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/perception')]
final class PerceptionController extends AbstractController
{
    #[Route('', name: 'app_perception_index', methods: ['GET'])]
    public function index(FactureRepository $factureRepository): Response
    {
        $factures = $factureRepository->createQueryBuilder('f')
            ->leftJoin('f.consultation', 'c')->addSelect('c')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('f.paiements', 'pa')->addSelect('pa')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $nbNonPayees = 0;
        $nbPartielles = 0;
        $nbPayees = 0;
        $totalEncaisseJour = 0;

        $today = (new \DateTimeImmutable())->format('Y-m-d');

        foreach ($factures as $facture) {
            $statut = $facture->getStatut()->value;

            if ($statut === 'non_paye') {
                $nbNonPayees++;
            } elseif ($statut === 'partiellement_paye') {
                $nbPartielles++;
            } elseif ($statut === 'paye') {
                $nbPayees++;
            }

            foreach ($facture->getPaiements() as $paiement) {
                if ($paiement->getPayeLe()->format('Y-m-d') === $today) {
                    $totalEncaisseJour += $paiement->getMontant();
                }
            }
        }

        return $this->render('perception/index.html.twig', [
            'factures' => $factures,
            'nbNonPayees' => $nbNonPayees,
            'nbPartielles' => $nbPartielles,
            'nbPayees' => $nbPayees,
            'totalEncaisseJour' => $totalEncaisseJour,
        ]);
    }

    #[Route('/facture/{id}', name: 'app_perception_facture_show', methods: ['GET'])]
    public function show(Facture $facture): Response
    {
        return $this->render('perception/show.html.twig', [
            'facture' => $facture,
        ]);
    }

    #[Route('/facture/{id}/encaisser', name: 'app_perception_facture_encaisser', methods: ['GET', 'POST'])]
    public function encaisser(
        Request $request,
        Facture $facture,
        FacturationService $facturationService,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(EncaissementType::class, null, [
            'action' => $this->generateUrl('app_perception_facture_encaisser', ['id' => $facture->getId()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                $facturationService->ajouterPaiement(
                    $facture,
                    (int) $data['montant'],
                    $data['mode']
                );

                $em->flush();

                return $this->json([
                    'success' => true,
                    'message' => 'Paiement enregistré avec succès.',
                    'printUrl' => $this->generateUrl('app_perception_facture_print', [
                        'id' => $facture->getId(),
                    ]),
                ]);
            }

            return $this->render('perception/_encaissement_form.html.twig', [
                'form' => $form->createView(),
                'facture' => $facture,
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $facturationService->ajouterPaiement(
                $facture,
                (int) $data['montant'],
                $data['mode']
            );

            $em->flush();

            return $this->redirectToRoute('app_perception_facture_show', [
                'id' => $facture->getId(),
            ]);
        }

        return $this->render('perception/encaisser.html.twig', [
            'form' => $form->createView(),
            'facture' => $facture,
        ]);
    }
    #[Route('/facture/{id}/print', name: 'app_perception_facture_print', methods: ['GET'])]
    public function print(Facture $facture): Response
    {
        return $this->render('perception/print.html.twig', [
            'facture' => $facture,
        ]);
    }
}