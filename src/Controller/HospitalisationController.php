<?php

namespace App\Controller;

use App\Entity\Hospitalisation;
use App\Form\HospitalisationType;
use App\Form\HospitalisationBilanType; // ✅ NOUVEL IMPORT
use App\Repository\HospitalisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/hospitalisation')]
final class HospitalisationController extends AbstractController
{
    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_HOSPITALISATION') or is_granted('ROLE_MEDECIN') or is_granted('ROLE_INFIRMIER')"))]
    #[Route(name: 'app_hospitalisation_index', methods: ['GET','POST'])]
    public function index(Request $request, HospitalisationRepository $repository, EntityManagerInterface $em): Response 
    {
        $hospitalisation = new Hospitalisation();
        $form = $this->createForm(HospitalisationType::class, $hospitalisation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($hospitalisation);
            $em->flush();
            return $this->redirectToRoute('app_hospitalisation_index');
        }

        return $this->render('hospitalisation/index.html.twig', [
            'hospitalisations' => $repository->findBy([], ['id' => 'DESC']),
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_HOSPITALISATION') or is_granted('ROLE_MEDECIN') or is_granted('ROLE_INFIRMIER')"))]
    #[Route('/{id}', name: 'app_hospitalisation_show', methods: ['GET', 'POST'])] // ⚠️ Ajout de POST
    public function show(
        Request $request, // ⚠️ Ajout
        Hospitalisation $hospitalisation,
        EntityManagerInterface $entityManager // ⚠️ Ajout
    ): Response {
        
        // --- DÉBUT : LOGIQUE D'AJOUT D'OBSERVATION (Contexte Hospitalisation) ---
        $observation = new \App\Entity\ObservationMedicale();
        $formObservation = $this->createForm(\App\Form\ObservationMedicaleType::class, $observation);
        $formObservation->handleRequest($request);

        if ($formObservation->isSubmitted() && $formObservation->isValid()) {
            // Injections sécurisées
            $observation->setDossier($hospitalisation->getDossierMedical()); // Lien au dossier parent
            $observation->setHospitalisation($hospitalisation); // Lien explicite au séjour
            $observation->setMedecinAuteur($this->getUser());

            $entityManager->persist($observation);
            $entityManager->flush();

            $this->addFlash('success', 'La note d\'évolution a bien été ajoutée au suivi de l\'hospitalisation.');

            return $this->redirectToRoute('app_hospitalisation_show', ['id' => $hospitalisation->getId()]);
        }
        // --- FIN : LOGIQUE D'AJOUT D'OBSERVATION ---

        return $this->render('hospitalisation/show.html.twig', [
            'hospitalisation' => $hospitalisation,
            'formObservation' => $formObservation->createView(), // ⚠️ Envoi du formulaire à la vue
        ]);
    }

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_HOSPITALISATION') or is_granted('ROLE_MEDECIN') or is_granted('ROLE_INFIRMIER')"))]
    #[Route('/{id}/print', name: 'app_hospitalisation_print', methods: ['GET'])]
    public function print(Hospitalisation $hospitalisation): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $html = $this->renderView('hospitalisation/print.html.twig', [
            'hospitalisation' => $hospitalisation,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="hospitalisation-%d.pdf"', $hospitalisation->getId()),
        ]);
    }

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_HOSPITALISATION') or is_granted('ROLE_MEDECIN')"))]
    #[Route('/{id}/edit', name: 'app_hospitalisation_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Hospitalisation $hospitalisation, EntityManagerInterface $em): Response 
    {
        $form = $this->createForm(HospitalisationType::class, $hospitalisation, [
            'action' => $this->generateUrl('app_hospitalisation_edit', ['id' => $hospitalisation->getId()]),
        ]);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest() && $request->isMethod('GET')) {
            $html = $this->renderView('hospitalisation/_form.html.twig', [
                'form' => $form->createView(),
                'hospitalisation' => $hospitalisation,
            ]);
            return new JsonResponse(['form' => $html]);
        }

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            if ($form->isSubmitted() && $form->isValid()) {
                $em->flush();
                return new JsonResponse(['success' => true]);
            }

            // ✅ CORRECTION DU BUG AJAX (On renvoie du JSON même en cas d'erreur)
            $html = $this->renderView('hospitalisation/_form.html.twig', [
                'form' => $form->createView(),
                'hospitalisation' => $hospitalisation,
            ]);
            return new JsonResponse(['success' => false, 'form' => $html]);
        }

        return $this->render('hospitalisation/_form.html.twig', [
            'form' => $form->createView(),
            'hospitalisation' => $hospitalisation,
        ]);
    }

    // ✅ NOUVELLE MÉTHODE : Gestion indépendante du Bilan et Diagnostic
    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_MEDECIN')"))]
    #[Route('/{id}/edit-bilan', name: 'app_hospitalisation_edit_bilan', methods: ['GET','POST'])]
    public function editBilan(Request $request, Hospitalisation $hospitalisation, EntityManagerInterface $em): Response 
    {
        // On génère l'URL d'action pour que le formulaire pointe bien vers cette méthode
        $form = $this->createForm(HospitalisationBilanType::class, $hospitalisation, [
            'action' => $this->generateUrl('app_hospitalisation_edit_bilan', ['id' => $hospitalisation->getId()]),
        ]);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest() && $request->isMethod('GET')) {
            $html = $this->renderView('hospitalisation/_form_bilan.html.twig', [
                'form' => $form->createView(),
                'hospitalisation' => $hospitalisation,
            ]);
            return new JsonResponse(['form' => $html]);
        }

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            if ($form->isSubmitted() && $form->isValid()) {
                $em->flush();
                return new JsonResponse(['success' => true]);
            }

            // Renvoyer les erreurs en JSON
            $html = $this->renderView('hospitalisation/_form_bilan.html.twig', [
                'form' => $form->createView(),
                'hospitalisation' => $hospitalisation,
            ]);
            return new JsonResponse(['success' => false, 'form' => $html]);
        }

        // Fallback standard
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Bilan mis à jour.');
            return $this->redirectToRoute('app_hospitalisation_show', ['id' => $hospitalisation->getId()]);
        }

        return $this->render('hospitalisation/_form_bilan.html.twig', [
            'form' => $form->createView(),
            'hospitalisation' => $hospitalisation,
        ]);
    }

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_MEDECIN') or is_granted('ROLE_INFIRMIER')"))]
    #[Route('/{id}/add-constantes', name: 'app_hospitalisation_add_constantes', methods: ['GET','POST'])]
    public function addConstantes(Request $request, Hospitalisation $hospitalisation, EntityManagerInterface $em): Response 
    {
        $examen = new \App\Entity\ExamenClinique();
        $examen->setHospitalisation($hospitalisation);
        
        $form = $this->createForm(\App\Form\ExamenCliniqueType::class, $examen, [
            'action' => $this->generateUrl('app_hospitalisation_add_constantes', ['id' => $hospitalisation->getId()]),
        ]);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod('POST')) {
                if ($form->isSubmitted() && $form->isValid()) {
                    $em->persist($examen);
                    $em->flush();
                    return new JsonResponse(['success' => true]);
                }
                
                // Formulaire invalide : on renvoie les erreurs
                $html = $this->renderView('hospitalisation/_form_constantes.html.twig', [
                    'form' => $form->createView()
                ]);
                return new JsonResponse(['success' => false, 'form' => $html]);
            }
            
            // GET AJAX : on renvoie le formulaire vierge
            $html = $this->renderView('hospitalisation/_form_constantes.html.twig', [
                'form' => $form->createView()
            ]);
            return new JsonResponse(['form' => $html]);
        }

        // Fallback si on tente d'accéder sans AJAX
        return $this->redirectToRoute('app_hospitalisation_show', ['id' => $hospitalisation->getId()]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_hospitalisation_delete', methods: ['POST'])]
    public function delete(Request $request, Hospitalisation $hospitalisation, EntityManagerInterface $em): Response 
    {
        if ($this->isCsrfTokenValid('delete'.$hospitalisation->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($hospitalisation);
            $em->flush();
        }
        return $this->redirectToRoute('app_hospitalisation_index');
    }
}