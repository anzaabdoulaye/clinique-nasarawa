<?php

namespace App\Controller;

use App\Entity\ServiceMedical;
use App\Form\ServiceMedicalType;
use App\Repository\ServiceMedicalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/service/medical')]
final class ServiceMedicalController extends AbstractController
{

    #[Route(name: 'app_service_medical_index', methods: ['GET'])]
    public function index(ServiceMedicalRepository $repo): Response
    {
        return $this->render('service_medical/index.html.twig', [
            'service_medicals' => $repo->findAll(),
        ]);
    }

    // ✅ ENDPOINT MODAL (AJAX)
    #[Route('/new-modal', name: 'app_service_medical_new_modal', methods: ['GET', 'POST'])]
    public function newModal(Request $request, EntityManagerInterface $em): Response
    {
        $serviceMedical = new ServiceMedical();
        $form = $this->createForm(ServiceMedicalType::class, $serviceMedical);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest() && $form->isSubmitted() && $form->isValid()) {
            $em->persist($serviceMedical);
            $em->flush();

            return $this->json(['success' => true]);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/param.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        return $this->redirectToRoute('app_service_medical_index');
    }

    #[Route('/new', name: 'app_service_medical_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $serviceMedical = new ServiceMedical();
        $form = $this->createForm(ServiceMedicalType::class, $serviceMedical);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($serviceMedical);
            $em->flush();

            return $this->redirectToRoute('app_service_medical_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('service_medical/new.html.twig', [
            'service_medical' => $serviceMedical,
            'form' => $form->createView(), // ✅ createView
        ]);
    }

    // ✅ IMPORTANT: contraindre id à un entier
    #[Route('/{id<\d+>}', name: 'app_service_medical_show', methods: ['GET'])]
    public function show(ServiceMedical $serviceMedical): Response
    {
        return $this->render('service_medical/show.html.twig', [
            'service_medical' => $serviceMedical,
        ]);
    }

   #[Route('/{id<\d+>}/edit', name: 'app_service_medical_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ServiceMedical $serviceMedical, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ServiceMedicalType::class, $serviceMedical);
        $form->handleRequest($request);

        // ✅ Mode AJAX : utilisé par ton modal (fetch avec X-Requested-With)
        if ($request->isXmlHttpRequest()) {

            if ($form->isSubmitted() && $form->isValid()) {
                $em->flush();

                return $this->json([
                    'success' => true,
                    'message' => 'Service médical modifié.',
                ]);
            }

            // GET ajax (ou POST avec erreurs) => renvoyer uniquement le HTML du form
            return $this->render('service_medical/edit.html.twig', [
                'service_medical' => $serviceMedical,
                'form' => $form->createView(),
            ]);
        }

        // ✅ Mode normal (si tu ouvres /edit dans le navigateur)
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_service_medical_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('service_medical/edit.html.twig', [
            'service_medical' => $serviceMedical,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_service_medical_delete', methods: ['POST'])]
    //#[Route('/{id<\d+>}/edit', name: 'app_service_medical_edit', methods: ['GET','POST'])]
    public function delete(Request $request, ServiceMedical $serviceMedical, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$serviceMedical->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($serviceMedical);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_service_medical_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/create-ajax', name: 'app_service_medical_create_ajax', methods: ['POST'])]
    public function createAjax(Request $request, EntityManagerInterface $em): Response
    {
        $service = new ServiceMedical();
        $form = $this->createForm(ServiceMedicalType::class, $service);
        $form->handleRequest($request);

        // On force le mode AJAX (sinon fallback)
        if (!$request->isXmlHttpRequest()) {
            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($service);
                $em->flush();
            }
            return $this->redirectToRoute('app_utilisateur_param');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($service);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Service médical ajouté.',
            ]);
        }

        // ❗ Erreurs validation : renvoyer le HTML du form (sans partial)
        $html = $this->renderView('service_medical/create_ajax_response.html.twig', [
            'serviceForm' => $form->createView(),
        ]);

        return $this->json([
            'success' => false,
            'html' => $html,
        ], 422);
    }

}
