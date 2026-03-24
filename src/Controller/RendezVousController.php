<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\RendezVous;
use App\Enum\StatutConsultation;
use App\Enum\StatutRendezVous;
use App\Form\RendezVousType;
use App\Repository\ConsultationRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Dompdf\Dompdf;
use Dompdf\Options;
use setasign\Fpdi\Fpdi;
use Endroid\QrCode\Encoding\Encoding;

#[Route('/rendez/vous')]
final class RendezVousController extends AbstractController
{
   #[Route(name: 'app_rendez_vous_index', methods: ['GET', 'POST'])]
public function index(
    Request $request,
    RendezVousRepository $rendezVousRepository,
    EntityManagerInterface $em
): Response {
    $rv = new RendezVous();
    $form = $this->createForm(RendezVousType::class, $rv);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $rv->setConsultation(null);

        if (!$rv->getStatut()) {
            $rv->setStatut(StatutRendezVous::EN_ATTENTE);
        }

        $em->persist($rv);
        $em->flush();

        return $this->redirectToRoute('app_rendez_vous_index');
    }

    $search = $request->query->get('search');

    return $this->render('rendez_vous/index.html.twig', [
        'rendezVous' => $rendezVousRepository->findBySearchTerm($search),
        'form' => $form->createView(),
        'search' => $search,
    ]);
}

    #[Route('/{id}', name: 'app_rendez_vous_show', methods: ['GET'])]
    public function show(RendezVous $rendezVou): Response
    {
        return $this->render('rendez_vous/show.html.twig', [
            'rendez_vou' => $rendezVou,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_rendez_vous_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RendezVous $rendezVous, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RendezVousType::class, $rendezVous);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }
            return $this->redirectToRoute('app_rendez_vous_index');
        }

        // AJAX: renvoyer uniquement le contenu du form (pour la modale)
        if ($request->isXmlHttpRequest()) {
            return $this->render('rendez_vous/edit.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        // Hors AJAX: page normale
        return $this->render('rendez_vous/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_rendez_vous_delete', methods: ['POST'])]
    public function delete(Request $request, RendezVous $rendezVou, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rendezVou->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($rendezVou);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rendez_vous_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/start-consultation', name: 'app_rendez_vous_start_consultation', methods: ['POST'])]
public function startConsultation(
    Request $request,
    RendezVous $rendezVous,
    ConsultationRepository $consultationRepo,
    EntityManagerInterface $em
): Response {
    if (!$this->isCsrfTokenValid('start_consultation_' . $rendezVous->getId(), (string)$request->request->get('_token'))) {
        throw $this->createAccessDeniedException('CSRF token invalide.');
    }

    // ✅ check DB
    $existing = $consultationRepo->findOneBy(['rendezVous' => $rendezVous]);
    if ($existing) {
        $this->addFlash('info', 'Consultation déjà créée.');
        return $this->redirectToRoute('app_consultation_show', ['id' => $existing->getId()]);
    }

    // ... tes checks statut RDV, patient, médecin, dossier

    $consultation = new Consultation();
    $consultation->setRendezVous($rendezVous); // sync auto RDV->consultation
    $consultation->setMedecin($rendezVous->getMedecin());
    $consultation->setDossierMedical($rendezVous->getPatient()->getDossierMedical());
    $consultation->setStatut(StatutConsultation::EN_COURS);

    $em->persist($consultation);
    $em->flush();

    return $this->redirectToRoute('app_consultation_show', ['id' => $consultation->getId()]);
}

    #[Route('/{id}/print', name: 'app_rendez_vous_print', methods: ['GET'])]
    public function print(RendezVous $rendezVous): Response
    {
        $showUrl = $this->generateUrl('app_rendez_vous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $qrCode = new QrCode(
            data: $showUrl,
            encoding: new Encoding('UTF-8'),
            size: 200,
            margin: 6
        );

        $png = (new PngWriter())->write($qrCode)->getString();
        $dataUri = 'data:image/png;base64,' . base64_encode($png);

        $code = 'RV-' . $rendezVous->getId();

        return $this->render('rendez_vous/print.html.twig', [
            'rendez_vous' => $rendezVous,
            'qr_data' => $dataUri,
            'code_qr' => $code,
            'verifyUrl' => $showUrl,
        ]);
    }

    #[Route('/{id}/print/pdf', name: 'app_rendez_vous_print_pdf', methods: ['GET'])]
    public function printPdf(RendezVous $rendezVous): Response
    {
        $showUrl = $this->generateUrl('app_rendez_vous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

         $qrCode = new QrCode(
            data: $showUrl,
            encoding: new Encoding('UTF-8'),
            size: 200,
            margin: 6
        );
        $png = (new PngWriter())->write($qrCode)->getString();
        $dataUri = 'data:image/png;base64,' . base64_encode($png);

        $code = 'RV-' . $rendezVous->getId();

        $html = $this->renderView('rendez_vous/print.html.twig', [
            'rendez_vous' => $rendezVous,
            'qr_data' => $dataUri,
            'code_qr' => $code,
            'verifyUrl' => $showUrl,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfOutput = $dompdf->output();

        $extraPath = $this->getParameter('kernel.project_dir') . '/public/pdf/ANNEXE_RDV_VERSO.pdf';
        if (file_exists($extraPath)) {
            $temp = sys_get_temp_dir() . '/rendez_vous_' . $rendezVous->getId() . '.pdf';
            file_put_contents($temp, $pdfOutput);

            $fpdi = new Fpdi();
            $count1 = $fpdi->setSourceFile($temp);
            for ($p = 1; $p <= $count1; $p++) {
                $tpl = $fpdi->importPage($p);
                $size = $fpdi->getTemplateSize($tpl);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($tpl);
            }

            $count2 = $fpdi->setSourceFile($extraPath);
            for ($p = 1; $p <= $count2; $p++) {
                $tpl = $fpdi->importPage($p);
                $size = $fpdi->getTemplateSize($tpl);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($tpl);
            }

            $merged = $fpdi->Output('S');

            return new Response($merged, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="rendez_vous-%d.pdf"', $rendezVous->getId()),
            ]);
        }

        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="rendez_vous-%d.pdf"', $rendezVous->getId()),
        ]);
    }
}
