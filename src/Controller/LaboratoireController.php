<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\PrescriptionPrestation;
use App\Entity\ResultatLaboratoire;
use App\Entity\ResultatLaboratoireLigne;
use App\Enum\StatutPrescriptionPrestation;
use App\Form\ResultatLaboratoireType;
use App\Repository\PrescriptionPrestationRepository;
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
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/laboratoire')]
final class LaboratoireController extends AbstractController
{
    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO') or is_granted('ROLE_MEDECIN')"
))]
    #[Route('', name: 'app_laboratoire_index', methods: ['GET'])]
    public function index(PrescriptionPrestationRepository $repository): Response
    {
        $aTraiter = $repository->findExamensLaboAPrendreEnCharge();
        $enCours = $repository->findExamensLaboEnCours();
        $realises = $repository->findExamensLaboRealises();

        return $this->render('laboratoire/index.html.twig', [
            'aTraiter' => $aTraiter,
            'enCours' => $enCours,
            'realises' => $realises,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO') or is_granted('ROLE_MEDECIN')"
))]
    #[Route('/prestation/{id}', name: 'app_laboratoire_show', methods: ['GET'])]
    public function show(PrescriptionPrestation $prestation): Response
    {
        $this->verifierDestinationLaboratoire($prestation);

        $resultat = $prestation->getResultatLaboratoire();
        $hasResultatSaisi = $resultat ? $this->hasSaisiResultat($resultat) : false;

        return $this->render('laboratoire/show.html.twig', [
            'prestation' => $prestation,
            'canEditResult' => $this->canEditResult($prestation),
            'hasResultatSaisi' => $hasResultatSaisi,
            'canMarkRealise' => $prestation->getStatut() === StatutPrescriptionPrestation::EN_COURS && $hasResultatSaisi,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO')"
))]
    #[Route('/prestation/{id}/prendre-en-charge', name: 'app_laboratoire_prendre_en_charge', methods: ['POST'])]
    public function prendreEnCharge(
        PrescriptionPrestation $prestation,
        EntityManagerInterface $em
    ): Response {
        $this->verifierDestinationLaboratoire($prestation);

        if ($prestation->getStatut() === StatutPrescriptionPrestation::PAYE) {
            $prestation->setStatut(StatutPrescriptionPrestation::EN_COURS);
            $em->flush();
        }

        return $this->redirectToRoute('app_laboratoire_show', [
            'id' => $prestation->getId(),
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO')"
))]
    #[Route('/prestation/{id}/realiser', name: 'app_laboratoire_realiser', methods: ['POST'])]
    public function realiser(
        PrescriptionPrestation $prestation,
        EntityManagerInterface $em
    ): Response {
        $this->verifierDestinationLaboratoire($prestation);

        if ($prestation->getStatut() !== StatutPrescriptionPrestation::EN_COURS) {
            $this->addFlash('warning', 'Vous devez d\'abord prendre en charge cet examen avant de le marquer comme realise.');

            return $this->redirectToRoute('app_laboratoire_show', [
                'id' => $prestation->getId(),
            ]);
        }

        $resultat = $prestation->getResultatLaboratoire();
        if (!$resultat || !$this->hasSaisiResultat($resultat)) {
            $this->addFlash('warning', 'Vous devez saisir le resultat avant de marquer cet examen comme realise.');

            return $this->redirectToRoute('app_laboratoire_show', [
                'id' => $prestation->getId(),
            ]);
        }

        $prestation->setStatut(StatutPrescriptionPrestation::REALISE);
        $em->flush();

        return $this->redirectToRoute('app_laboratoire_show', [
            'id' => $prestation->getId(),
        ]);
    }

    private function verifierDestinationLaboratoire(PrescriptionPrestation $prestation): void
    {
        $service = $prestation->getTarifPrestation()?->getServiceExecution();

        if ($service !== 'laboratoire') {
            throw $this->createNotFoundException('Cette prestation ne relève pas du laboratoire.');
        }
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO') or is_granted('ROLE_MEDECIN')"
))]
    #[Route('/bon/consultation/{id}', name: 'app_laboratoire_bon_show', methods: ['GET'])]
    public function bonShow(
        Consultation $consultation,
        PrescriptionPrestationRepository $repository
    ): Response {
        $examens = $repository->findExamensLaboPayesParConsultation($consultation->getId());

        if (count($examens) === 0) {
            $this->addFlash('warning', 'Aucun examen laboratoire payé trouvé pour cette consultation.');
            return $this->redirectToRoute('app_laboratoire_index');
        }

        return $this->render('laboratoire/bon_show.html.twig', [
            'consultation' => $consultation,
            'examens' => $examens,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO') or is_granted('ROLE_MEDECIN')"
))]
    #[Route('/bon/consultation/{id}/print', name: 'app_laboratoire_bon_print', methods: ['GET'])]
    public function bonPrint(
        Consultation $consultation,
        PrescriptionPrestationRepository $repository
    ): Response {
        $examens = $repository->findExamensLaboPayesParConsultation($consultation->getId());

        if (count($examens) === 0) {
            throw $this->createNotFoundException('Aucun examen laboratoire imprimable pour cette consultation.');
        }

        return $this->render('laboratoire/bon_print.html.twig', [
            'consultation' => $consultation,
            'examens' => $examens,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO')"
))]
    #[Route('/prestation/{id}/resultat', name: 'app_laboratoire_resultat_edit', methods: ['GET', 'POST'])]
public function saisirResultat(
    Request $request,
    PrescriptionPrestation $prestation,
    EntityManagerInterface $em
): Response {
    $this->verifierDestinationLaboratoire($prestation);

    if (!$this->canEditResult($prestation)) {
        $message = 'Vous devez d\'abord prendre en charge cet examen avant de saisir le resultat.';

        if ($request->isXmlHttpRequest()) {
            return new Response(sprintf(
                '<div class="modal-body"><div class="alert alert-warning mb-0">%s</div></div>',
                htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            ), Response::HTTP_FORBIDDEN);
        }

        $this->addFlash('warning', $message);

        return $this->redirectToRoute('app_laboratoire_show', [
            'id' => $prestation->getId(),
        ]);
    }

    $resultat = $prestation->getResultatLaboratoire();
    if (!$resultat) {
        $resultat = new ResultatLaboratoire();
        $resultat->setPrescriptionPrestation($prestation);
        $prestation->setResultatLaboratoire($resultat);

        $ligne = new ResultatLaboratoireLigne();
        $ligne->setDemande($prestation->getTarifPrestation()?->getLibelle() ?? 'Examen');
        $ligne->setOrdre(1);
        $resultat->addLigne($ligne);
    }

    $form = $this->createForm(ResultatLaboratoireType::class, $resultat, [
        'action' => $this->generateUrl('app_laboratoire_resultat_edit', [
            'id' => $prestation->getId(),
        ]),
        'method' => 'POST',
    ]);

    $form->handleRequest($request);

    if ($request->isXmlHttpRequest()) {
        if ($form->isSubmitted() && $form->isValid()) {
            $resultat->setDateValidation(new \DateTimeImmutable());
            $resultat->setValidePar($this->buildLaborantinLabel($this->getUser()));

            $em->persist($resultat);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Résultat laboratoire enregistré avec succès.',
            ]);
        }

        return $this->render('laboratoire/_resultat_form.html.twig', [
            'form' => $form->createView(),
            'prestation' => $prestation,
            'resultat' => $resultat,
        ]);
    }

    if ($form->isSubmitted() && $form->isValid()) {
        $resultat->setDateValidation(new \DateTimeImmutable());
        $resultat->setValidePar($this->buildLaborantinLabel($this->getUser()));

        $em->persist($resultat);
        $em->flush();

        return $this->redirectToRoute('app_laboratoire_show', [
            'id' => $prestation->getId(),
        ]);
    }

    return $this->render('laboratoire/resultat_form.html.twig', [
        'prestation' => $prestation,
        'form' => $form->createView(),
        'resultat' => $resultat,
    ]);
}

#[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO') or is_granted('ROLE_MEDECIN')"
))]
    #[Route('/prestation/{id}/resultat/print', name: 'app_laboratoire_resultat_print', methods: ['GET'])]
    public function imprimerResultat(PrescriptionPrestation $prestation): Response
    {
        $this->verifierDestinationLaboratoire($prestation);

        $resultat = $prestation->getResultatLaboratoire();
        if (!$resultat) {
            throw $this->createNotFoundException('Aucun résultat laboratoire disponible pour cette prestation.');
        }

        $verifyUrl = $this->generateUrl('app_laboratoire_resultat_print', ['id' => $prestation->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        
        $qrCode = new QrCode(
            data: $verifyUrl,
            encoding: new Encoding('UTF-8'),
            size: 200,
            margin: 6
        );

        $png2 = (new PngWriter())->write($qrCode)->getString();
        $dataUri2 = 'data:image/png;base64,' . base64_encode($png2);

        $code = 'R-' . $resultat->getId();

        $logoBase64 = $this->getEmbeddedLogo();

        // also provide a PDF endpoint
        return $this->render('laboratoire/resultat_print.html.twig', [
            'prestation' => $prestation,
            'resultat' => $resultat,
            'qr_data' => $dataUri2,
            'code_qr' => $code,
            'verifyUrl' => $verifyUrl,
            'logo_path' => $logoBase64,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO') or is_granted('ROLE_MEDECIN')"
))]
    #[Route('/prestation/{id}/resultat/pdf', name: 'app_laboratoire_resultat_pdf', methods: ['GET'])]
    public function imprimerResultatPdf(PrescriptionPrestation $prestation): Response
    {
        $this->verifierDestinationLaboratoire($prestation);

        $resultat = $prestation->getResultatLaboratoire();
        if (!$resultat) {
            throw $this->createNotFoundException('Aucun résultat laboratoire disponible pour cette prestation.');
        }

        $verifyUrl = $this->generateUrl('app_laboratoire_resultat_print', ['id' => $prestation->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $qrCode = new QrCode(
            data: $verifyUrl,
            encoding: new Encoding('UTF-8'),
            size: 200,
            margin: 6
        );

        $png2 = (new PngWriter())->write($qrCode)->getString();
        $dataUri2 = 'data:image/png;base64,' . base64_encode($png2);

        $code = 'R-' . $resultat->getId();

        $logoBase64 = $this->getEmbeddedLogo();

        $html = $this->renderView('laboratoire/resultat_print.html.twig', [
            'prestation' => $prestation,
            'resultat' => $resultat,
            'qr_data' => $dataUri2,
            'code_qr' => $code,
            'verifyUrl' => $verifyUrl,
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

        $extraPath = $this->getParameter('kernel.project_dir') . '/public/pdf/ANNEXE_LABO_VERSO.pdf';
        if (file_exists($extraPath)) {
            $temp = sys_get_temp_dir() . '/resultat_' . $prestation->getId() . '.pdf';
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
                'Content-Disposition' => sprintf('inline; filename="resultat_labo-%d.pdf"', $prestation->getId()),
            ]);
        }

        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="resultat_labo-%d.pdf"', $prestation->getId()),
        ]);
    }

    private function buildLaborantinLabel(?object $user): ?string
    {
        if (!$user) {
            return null;
        }

        $fullName = method_exists($user, 'getNomComplet') ? trim((string) $user->getNomComplet()) : '';
        $account = method_exists($user, 'getUserIdentifier') ? trim((string) $user->getUserIdentifier()) : '';

        if ($fullName !== '' && $account !== '') {
            return sprintf('%s | Compte: %s', $fullName, $account);
        }

        if ($fullName !== '') {
            return $fullName;
        }

        return $account !== '' ? $account : null;
    }

    private function getEmbeddedLogo(): ?string
    {
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo.jpeg';

        if (!file_exists($logoPath)) {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
    }

    private function canEditResult(PrescriptionPrestation $prestation): bool
    {
        return \in_array($prestation->getStatut(), [
            StatutPrescriptionPrestation::EN_COURS,
            StatutPrescriptionPrestation::REALISE,
        ], true);
    }

    private function hasSaisiResultat(ResultatLaboratoire $resultat): bool
    {
        if (trim((string) $resultat->getConclusion()) !== '') {
            return true;
        }

        if (trim((string) $resultat->getResultat()) !== '') {
            return true;
        }

        foreach ($resultat->getLignes() as $ligne) {
            if (trim((string) $ligne->getResultat()) !== '') {
                return true;
            }
        }

        return false;
    }
}