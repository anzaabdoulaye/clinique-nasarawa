<?php

namespace App\Controller;

use App\Repository\LotRepository;
use App\Repository\MedicamentRepository;
use App\Repository\VenteRepository;
use App\Service\PharmacyService;
use App\Entity\Vente;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Dompdf\Dompdf;
use Dompdf\Options;
use setasign\Fpdi\Fpdi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Endroid\QrCode\Encoding\Encoding;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/pharmacie')]
final class PharmacieController extends AbstractController
{
    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_PHARMACIE')"
))]
    #[Route('/', name: 'app_pharmacie_index', methods: ['GET'])]
    public function index(
        MedicamentRepository $medicamentRepository,
        LotRepository $lotRepository,
        VenteRepository $venteRepository,
        PharmacyService $pharmacyService
    ): Response {
        // Statistiques générales
        $totalMedicaments = $medicamentRepository->count([]);
        $totalLots = $lotRepository->count([]);

        // Lots proches de péremption
        $nearExpirationLots = $pharmacyService->getLotsNearExpiration(30);
        $nearExpirationCount = count($nearExpirationLots);

        // Médicaments à stock faible
        $threshold = 10; // seuil modifiable
        $allMedicaments = $medicamentRepository->findAll();
        $lowStockMedicaments = [];

        foreach ($allMedicaments as $medicament) {
            $qty = $pharmacyService->getAvailableQuantity($medicament);

            if ($qty <= $threshold) {
                $lowStockMedicaments[] = [
                    'id' => $medicament->getId(),
                    'nom' => $medicament->getNom(),
                    'quantite' => $qty,
                ];
            }
        }

        usort($lowStockMedicaments, fn(array $a, array $b) => $a['quantite'] <=> $b['quantite']);
        $lowStockCount = count($lowStockMedicaments);

        // Ventes du jour
        $start = new \DateTimeImmutable('today');
        $end = $start->modify('+1 day');

        $ventesTodayList = $venteRepository->createQueryBuilder('v')
            ->andWhere('v.date >= :start')
            ->andWhere('v.date < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        $ventesToday = count($ventesTodayList);
        $chiffreAffairesToday = 0;

        foreach ($ventesTodayList as $vente) {
            // Adaptez selon votre entité Vente
            if (method_exists($vente, 'getMontantTotal')) {
                $chiffreAffairesToday += (int) $vente->getMontantTotal();
            } elseif (method_exists($vente, 'getTotal')) {
                $chiffreAffairesToday += (int) $vente->getTotal();
            }
        }

        return $this->render('pharmacie/index.html.twig', [
            'totalMedicaments' => $totalMedicaments,
            'totalLots' => $totalLots,
            'lowStockCount' => $lowStockCount,
            'nearExpirationCount' => $nearExpirationCount,
            'ventesToday' => $ventesToday,
            'chiffreAffairesToday' => $chiffreAffairesToday,
            'lowStockMedicaments' => $lowStockMedicaments,
            'nearExpirationLots' => $nearExpirationLots,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_PHARMACIE')"
))]
    #[Route('/vente/{id}/print', name: 'app_pharmacie_vente_print', methods: ['GET'])]
    public function printVente(Vente $vente): Response
    {
        $verifyUrl = $this->generateUrl('app_pharmacie_vente_print', ['id' => $vente->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $qrCode = new QrCode(
            data: $verifyUrl,
            encoding: new Encoding('UTF-8'),
            size: 200,
            margin: 6
        );
        $png = (new PngWriter())->write($qrCode)->getString();
        $dataUri = 'data:image/png;base64,' . base64_encode($png);

        $code = 'VTE-' . $vente->getId();

        return $this->render('pharmacie/print_caisse.html.twig', [
            'vente' => $vente,
            'qr_data' => $dataUri,
            'code_qr' => $code,
            'verifyUrl' => $verifyUrl,
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_PHARMACIE')"
))]
    #[Route('/vente/{id}/pdf', name: 'app_pharmacie_vente_pdf', methods: ['GET'])]
    public function printVentePdf(Vente $vente): Response
    {
        $verifyUrl = $this->generateUrl('app_pharmacie_vente_print', ['id' => $vente->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $qrCode = new QrCode(
            data: $verifyUrl,
            encoding: new Encoding('UTF-8'),
            size: 200,
            margin: 6
        );
        $png = (new PngWriter())->write($qrCode)->getString();
        $dataUri = 'data:image/png;base64,' . base64_encode($png);

        $code = 'VTE-' . $vente->getId();

        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo.jpeg';
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));

        $html = $this->renderView('pharmacie/print_caisse.html.twig', [
            'vente' => $vente,
            'qr_data' => $dataUri,
            'code_qr' => $code,
            'logo_path' => $logoBase64,
            'verifyUrl' => $verifyUrl,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();

        $pdfOutput = $dompdf->output();

        $extraPath = $this->getParameter('kernel.project_dir') . '/public/pdf/ANNEXE_CAISSE_VERSO.pdf';
        if (file_exists($extraPath)) {
            $temp = sys_get_temp_dir() . '/vente_' . $vente->getId() . '.pdf';
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
                'Content-Disposition' => sprintf('inline; filename="vente-%d.pdf"', $vente->getId()),
            ]);
        }

        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="vente-%d.pdf"', $vente->getId()),
        ]);
    }
}