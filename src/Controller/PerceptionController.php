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
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use setasign\Fpdi\Fpdi;

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
        $verifyUrl = $this->generateUrl('app_facture_print', ['id' => $facture->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $qrCode = new QrCode(
            data: $verifyUrl,
            encoding: new Encoding('UTF-8'),
            size: 240,
            margin: 8
        );

        $png = (new PngWriter())->write($qrCode)->getString();
        $dataUri = 'data:image/png;base64,' . base64_encode($png);

        $code = 'FAC-' . $facture->getId();

        return $this->render('perception/print.html.twig', [
            'facture' => $facture,
            'qr_data' => $dataUri,
            'code_qr' => $code,
            'verifyUrl' => $verifyUrl,
            'consultation' => $facture->getConsultation(),
        ]);
    }

    #[Route('/facture/{id}/pdf', name: 'app_perception_facture_pdf', methods: ['GET'])]
    public function printPdf(Facture $facture): Response
    {
        $verifyUrl = $this->generateUrl('app_facture_print', ['id' => $facture->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $qrCode = new QrCode(
            data: $verifyUrl,
            encoding: new Encoding('UTF-8'),
            size: 240,
            margin: 8
        );

        $png = (new PngWriter())->write($qrCode)->getString();
        $dataUri = 'data:image/png;base64,' . base64_encode($png);

        $code = 'FAC-' . $facture->getId();

        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo.jpeg';
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));

        $html = $this->renderView('perception/print.html.twig', [
            'facture' => $facture,
            'qr_data' => $dataUri,
            'code_qr' => $code,
            'verifyUrl' => $verifyUrl,
            'consultation' => $facture->getConsultation(),
            'logo_path' => $logoBase64,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();

        

        $pdfOutput = $dompdf->output();

        $extraPath = $this->getParameter('kernel.project_dir') . '/public/pdf/ANNEXE_FACTURE_VERSO.pdf';
        if (file_exists($extraPath)) {
            $temp = sys_get_temp_dir() . '/facture_' . $facture->getId() . '.pdf';
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
                'Content-Disposition' => sprintf('inline; filename="facture-%d.pdf"', $facture->getId()),
            ]);
        }

        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="facture-%d.pdf"', $facture->getId()),
        ]);
    }
}