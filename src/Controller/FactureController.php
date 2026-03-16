<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\Facture;
use App\Enum\ModePaiement;
use App\Form\FactureType;
use App\Repository\FactureRepository;
use App\Service\BillingService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\BuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/facture')]
final class FactureController extends AbstractController
{
    #[Route(name: 'app_facture_index', methods: ['GET'])]
    public function index(FactureRepository $factureRepository): Response
    {
        return $this->render('facture/index.html.twig', [
            'factures' => $factureRepository->findAll(),
            'factures' => $factureRepository->findAllWithRelations(),
        ]);
    }

    #[Route('/new', name: 'app_facture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $facture = new Facture();
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($facture);
            $entityManager->flush();

            return $this->redirectToRoute('app_facture_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('facture/new.html.twig', [
            'facture' => $facture,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_facture_show', methods: ['GET'])]
    public function show(Facture $facture): Response
    {
        return $this->render('facture/show.html.twig', [
            'facture' => $facture,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_facture_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_facture_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('facture/edit.html.twig', [
            'facture' => $facture,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_facture_delete', methods: ['POST'])]
    public function delete(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$facture->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($facture);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_facture_index', [], Response::HTTP_SEE_OTHER);
    }



    #[Route('/consultation/{id}/facture/modal', name: 'app_consultation_facture_modal', methods: ['GET'])]
    public function factureModal(
        Consultation $consultation,
        BillingService $billing,
        EntityManagerInterface $em
    ): Response {
        // Forfait consultation (à remplacer plus tard par un tarif)
        $forfait = 0; // ex: 5000

        $facture = $billing->generateDraftInvoice($consultation, $forfait);
        $em->flush();

        return $this->render('facture/_modal_facture.html.twig', [
            'consultation' => $consultation,
            'facture' => $facture,
        ]);
    }

    #[Route('/facture/{id}/payer', name: 'app_facture_payer', methods: ['POST'])]
    public function payer(
        Facture $facture,
        Request $request,
        BillingService $billing,
        EntityManagerInterface $em
    ): Response {
        $modeRaw = (string) $request->request->get('modePaiement', '');
        if ($modeRaw === '' || !ModePaiement::tryFrom($modeRaw)) {
            return $this->json(['success' => false, 'message' => 'Mode de paiement invalide.'], 422);
        }

        $billing->payInvoice($facture, ModePaiement::from($modeRaw));
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/facture/{id}/print', name: 'app_facture_print', methods: ['GET'])]
    public function print(Facture $facture): Response
    {
        return $this->render('facture/print.html.twig', [
            'facture' => $facture,
            'consultation' => $facture->getConsultation(),
            'patient' => $facture->getConsultation()->getRendezVous()->getPatient(), // adapte si besoin
        ]);
    }

#[Route('/facture/{id}/qr', name: 'app_facture_qr', methods: ['GET'])]
public function qrFacture(Facture $facture, BuilderInterface $builder): Response
{
    $payload = $this->generateUrl(
        'app_facture_print',
        ['id' => $facture->getId()],
        UrlGeneratorInterface::ABSOLUTE_URL
    );

    $result = $builder
        ->data($payload)
        ->size(300)
        ->margin(10)
        ->build();

    return new Response($result->getString(), 200, [
        'Content-Type' => $result->getMimeType(),
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    ]);
}
}
