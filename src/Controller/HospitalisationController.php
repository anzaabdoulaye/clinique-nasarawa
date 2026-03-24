<?php

namespace App\Controller;

use App\Entity\Hospitalisation;
use App\Form\HospitalisationType;
use App\Repository\HospitalisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted as AttributeIsGranted;

#[AttributeIsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/hospitalisation')]
final class HospitalisationController extends AbstractController
{

#[AttributeIsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_HOSPITALISATION') or is_granted('ROLE_MEDECIN') or is_granted('ROLE_INFIRMIER')"
))]
 #[Route(name: 'app_hospitalisation_index', methods: ['GET','POST'])]
    public function index(
        Request $request,
        HospitalisationRepository $repository,
        EntityManagerInterface $em
    ): Response {
        $hospitalisation = new Hospitalisation();
        $form = $this->createForm(HospitalisationType::class, $hospitalisation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($hospitalisation);
            $em->flush();

            return $this->redirectToRoute('app_hospitalisation_index');
        }

        $search = $request->query->get('search');

        return $this->render('hospitalisation/index.html.twig', [
            'hospitalisations' => $repository->findBySearchTerm($search),
            'form' => $form->createView(),
            'search' => $search,
        ]);
    }


    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_HOSPITALISATION') or is_granted('ROLE_MEDECIN') or is_granted('ROLE_INFIRMIER')"
))]
    #[Route('/{id}', name: 'app_hospitalisation_show', methods: ['GET'])]
    public function show(Hospitalisation $hospitalisation): Response
    {
        return $this->render('hospitalisation/show.html.twig', [
            'hospitalisation' => $hospitalisation,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_HOSPITALISATION') or is_granted('ROLE_MEDECIN') or is_granted('ROLE_INFIRMIER')"
))]
    #[Route('/{id}/print', name: 'app_hospitalisation_print', methods: ['GET'])]
    public function print(Hospitalisation $hospitalisation): Response
    {
        // configure Dompdf according to your needs
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        // render Twig template to HTML
        $html = $this->renderView('hospitalisation/print.html.twig', [
            'hospitalisation' => $hospitalisation,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // return PDF response
        $pdfOutput = $dompdf->output();

        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="hospitalisation-%d.pdf"', $hospitalisation->getId()),
        ]);
    }


    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_HOSPITALISATION') or is_granted('ROLE_MEDECIN')"
))]
    #[Route('/{id}/edit', name: 'app_hospitalisation_edit', methods: ['GET','POST'])]
    public function edit(
        Request $request,
        Hospitalisation $hospitalisation,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(HospitalisationType::class, $hospitalisation);
        $form->handleRequest($request);

        // === GET AJAX → Charger le formulaire dans le modal
        if ($request->isXmlHttpRequest() && $request->isMethod('GET')) {
            return new JsonResponse([
                'form' => $this->renderView('hospitalisation/_form.html.twig', [
                    'form' => $form->createView(),
                    'hospitalisation' => $hospitalisation,
                ])
            ]);
        }

        // === POST AJAX → Soumission du formulaire
        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {

            if ($form->isSubmitted() && $form->isValid()) {

                $em->flush();

                return new JsonResponse(['success' => true]);
            }

            // Récupérer erreurs
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }

            return new JsonResponse([
                'success' => false,
                'errors' => $errors,
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Requête invalide'
        ], Response::HTTP_BAD_REQUEST);
    }


    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_hospitalisation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Hospitalisation $hospitalisation,
        EntityManagerInterface $em
    ): Response {

        if ($this->isCsrfTokenValid(
            'delete'.$hospitalisation->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $em->remove($hospitalisation);
            $em->flush();
        }

        return $this->redirectToRoute('app_hospitalisation_index');
    }
}