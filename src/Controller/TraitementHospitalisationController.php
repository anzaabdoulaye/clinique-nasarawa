<?php

namespace App\Controller;

use App\Entity\TraitementHospitalisation;
use App\Entity\Hospitalisation;
use App\Form\TraitementHospitalisationType;
use App\Repository\TraitementHospitalisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/traitement/hospitalisation')]
final class TraitementHospitalisationController extends AbstractController
{
    #[Route(name: 'app_traitement_hospitalisation_index', methods: ['GET'])]
    public function index(TraitementHospitalisationRepository $repo): Response
    {
        return $this->render('traitement_hospitalisation/index.html.twig', [
            'traitement_hospitalisations' => $repo->findAll(),
        ]);
    }

 #[Route('/new/{hospitalisation}', name: 'app_traitement_hospitalisation_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    Hospitalisation $hospitalisation,
    EntityManagerInterface $entityManager
): Response {
    $traitement = new TraitementHospitalisation();
    $traitement->setHospitalisation($hospitalisation);

    $form = $this->createForm(TraitementHospitalisationType::class, $traitement);
    $form->handleRequest($request);

    // GET AJAX → charger le formulaire dans le modal
    if ($request->isXmlHttpRequest() && $request->isMethod('GET')) {
        return new JsonResponse([
            'form' => $this->renderView('traitement_hospitalisation/_form.html.twig', [
                'form' => $form->createView(),
                'hospitalisation' => $hospitalisation,
            ])
        ]);
    }

    // POST AJAX → soumission du formulaire
    if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($traitement);
            $entityManager->flush();

            return new JsonResponse(['success' => true]);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'errors' => $errors,
            'form' => $this->renderView('traitement_hospitalisation/_form.html.twig', [
                'form' => $form->createView(),
                'hospitalisation' => $hospitalisation,
            ])
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    
    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($traitement);
        $entityManager->flush();

        return $this->redirectToRoute('app_hospitalisation_show', [
            'id' => $hospitalisation->getId(),
        ]);
    }

    return $this->render('traitement_hospitalisation/new.html.twig', [
        'traitement' => $traitement,
        'form' => $form->createView(),
        'hospitalisation' => $hospitalisation,
    ]);
}
    #[Route('/{id}', name: 'app_traitement_hospitalisation_show', methods: ['GET'])]
    public function show(TraitementHospitalisation $traitement): Response
    {
        return $this->render('traitement_hospitalisation/show.html.twig', [
            'traitement_hospitalisation' => $traitement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_traitement_hospitalisation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        TraitementHospitalisation $traitement,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(TraitementHospitalisationType::class, $traitement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_hospitalisation_show', [
                'id' => $traitement->getHospitalisation()->getId()
            ]);
        }

        return $this->render('traitement_hospitalisation/edit.html.twig', [
            'traitement_hospitalisation' => $traitement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_traitement_hospitalisation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        TraitementHospitalisation $traitement,
        EntityManagerInterface $em
    ): Response {
        $hospId = $traitement->getHospitalisation()->getId();

        if ($this->isCsrfTokenValid(
            'delete' . $traitement->getId(),
            $request->request->get('_token')
        )) {
            $em->remove($traitement);
            $em->flush();
        }

        return $this->redirectToRoute('app_hospitalisation_show', [
            'id' => $hospId
        ]);
    }
}