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
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
public function new(Request $request, Hospitalisation $hospitalisation, EntityManagerInterface $entityManager): Response
{
    try {
        $traitement = new TraitementHospitalisation();
        $traitement->setHospitalisation($hospitalisation);

        $form = $this->createForm(TraitementHospitalisationType::class, $traitement, [
    'action' => $this->generateUrl('app_traitement_hospitalisation_new', [
        'hospitalisation' => $hospitalisation->getId()
    ]),
]);
$form->handleRequest($request);

        // GET AJAX → formulaire pour modal
        if ($request->isXmlHttpRequest() && $request->isMethod('GET')) {
            $html = $this->renderView('traitement_hospitalisation/_form.html.twig', [
                'form' => $form->createView(),
                'hospitalisation' => $hospitalisation,
            ]);
            return new JsonResponse(['form' => $html]);
        }

        // POST AJAX → soumission
     if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
    if ($form->isSubmitted()) {
        if ($form->isValid()) {
            try {
                $entityManager->persist($traitement);
                $entityManager->flush();

                $html = $this->renderView('hospitalisation/_traitement_rows.html.twig', [
                    'traitement' => $traitement,
                    'today' => (new \DateTimeImmutable())->format('Y-m-d'),
                    'nowTs' => time(),
                    'canRemoveAdministration' => $this->isGranted('ROLE_MEDECIN') || $this->isGranted('ROLE_ADMIN'),
                    'canDeleteTreatment' => $this->isGranted('ROLE_ADMIN'),
                ]);

                return new JsonResponse([
                    'success' => true,
                    'html' => $html,
                    'traitementId' => $traitement->getId(),
                ]);
            } catch (\Throwable $e) {
                return new JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'trace' => $this->getParameter('kernel.debug') ? $e->getTraceAsString() : null,
                ], 500);
            }
        } else {
            // LE CORRECTIF EST ICI : Le formulaire est invalide
            // On génère la vue HTML du formulaire (qui contiendra maintenant les erreurs)
            $html = $this->renderView('traitement_hospitalisation/_form.html.twig', [
                'form' => $form->createView(),
                'hospitalisation' => $hospitalisation,
            ]);
            
            // On la renvoie en JSON
            return new JsonResponse([
                'success' => false,
                'form' => $html
            ]);
        }
    }
}

        // Fallback non AJAX
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($traitement);
            $entityManager->flush();
            return $this->redirectToRoute('app_hospitalisation_show', ['id' => $hospitalisation->getId()]);
        }

        return $this->render('traitement_hospitalisation/new.html.twig', [
            'traitement' => $traitement,
            'form' => $form->createView(),
            'hospitalisation' => $hospitalisation,
        ]);
    } catch (\Throwable $e) {
        // Pour les requêtes AJAX, retourne l'erreur en JSON
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $this->getParameter('kernel.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
        throw $e;
    }
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

   

    // GET AJAX → récupérer le formulaire (optionnel)
    if ($request->isXmlHttpRequest() && $request->isMethod('GET')) {
        return new JsonResponse([
            'form' => $this->renderView('traitement_hospitalisation/_form.html.twig', [
                'form' => $form->createView(),
                'hospitalisation' => $traitement->getHospitalisation(),
            ])
        ]);
    }

    // POST AJAX
    if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            // Retourner les lignes mises à jour
            $html = $this->renderView('hospitalisation/_traitement_rows.html.twig', [
                'traitement' => $traitement,
                'today' => new \DateTimeImmutable(),
                'nowTs' => time(),
            ]);

            return new JsonResponse(['success' => true, 'html' => $html]);
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
                'hospitalisation' => $traitement->getHospitalisation(),
            ])
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Fallback non AJAX
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

    #[IsGranted('ROLE_ADMIN')]
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