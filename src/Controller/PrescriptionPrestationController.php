<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\PrescriptionPrestation;
use App\Form\PrescriptionPrestationType;
use App\Service\FacturationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prescription-prestation')]
final class PrescriptionPrestationController extends AbstractController
{
    #[Route('/consultation/{id}/prestation/new', name: 'app_prescription_prestation_new', methods: ['GET', 'POST'])]
    public function new(
    Request $request,
    Consultation $consultation,
    EntityManagerInterface $em,
    FacturationService $facturationService
    ): Response {
        $prescription = new PrescriptionPrestation();
        $prescription->setConsultation($consultation);

        $form = $this->createForm(PrescriptionPrestationType::class, $prescription, [
            'action' => $this->generateUrl('app_prescription_prestation_new', [
                'id' => $consultation->getId(),
            ]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($prescription);
                $facturationService->synchroniserDepuisPrescription($prescription);
                $em->flush();

                return $this->json([
                    'success' => true,
                    'message' => 'Prestation ajoutée avec succès.',
                ]);
            }

            return $this->render('consultation/prestations/_form.html.twig', [
                'form' => $form->createView(),
                'consultation' => $consultation,
                'prescription' => $prescription,
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($prescription);
            $facturationService->synchroniserDepuisPrescription($prescription);
            $em->flush();

            return $this->redirectToRoute('app_consultation_show', [
                'id' => $consultation->getId(),
            ]);
        }

        return $this->render('consultation/prestations/new.html.twig', [
            'form' => $form->createView(),
            'consultation' => $consultation,
            'prescription' => $prescription,
        ]);
    }
    #[Route('/{id}/edit', name: 'app_prescription_prestation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        PrescriptionPrestation $prescription,
        EntityManagerInterface $em,
        FacturationService $facturationService
    ): Response {
        $form = $this->createForm(PrescriptionPrestationType::class, $prescription, [
            'action' => $this->generateUrl('app_prescription_prestation_edit', [
                'id' => $prescription->getId(),
            ]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted() && $form->isValid()) {
                $em->flush();

                return $this->json([
                    'success' => true,
                    'message' => 'Prestation modifiée avec succès.',
                ]);
            }

            return $this->render('consultation/prestations/_form.html.twig', [
                'form' => $form->createView(),
                'consultation' => $prescription->getConsultation(),
                'prescription' => $prescription,
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $facturationService->synchroniserDepuisPrescription($prescription);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Prestation modifiée avec succès.',
            ]);
        }

        return $this->render('consultation/prestations/edit.html.twig', [
            'form' => $form->createView(),
            'consultation' => $prescription->getConsultation(),
            'prescription' => $prescription,
        ]);
    }

    #[Route('/{id}', name: 'app_prescription_prestation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        PrescriptionPrestation $prescription,
        EntityManagerInterface $em,
        FacturationService $facturationService
    ): Response {
        $consultation = $prescription->getConsultation();
        $consultationId = $consultation?->getId();

        if ($this->isCsrfTokenValid(
            'delete_prestation_' . $prescription->getId(),
            $request->request->get('_token')
        )) {
            $em->remove($prescription);
            $em->flush();

            if ($consultation) {
                $facturationService->synchroniserFactureDepuisConsultation($consultation);
                $em->flush();
            }
        }

        return $this->redirectToRoute('app_consultation_show', [
            'id' => $consultationId,
        ]);
    }
}