<?php

namespace App\Controller;

use App\Repository\FacturePECRepository;
use App\Repository\OrganismePriseEnChargeRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/perception/rapport/organismes')]
final class PerceptionRapportOrganismeController extends AbstractController
{
    #[Route('', name: 'app_perception_rapport_organisme', methods: ['GET'])]
    public function index(
        Request $request,
        FacturePECRepository $factureRepository,
        OrganismePriseEnChargeRepository $organismeRepository
    ): Response {
        $filtres = $this->extractFiltres($request);

        $rapport = $factureRepository->getRapportPrisesEnChargeParOrganisme(
            $filtres['search'],
            $filtres['organisme'],
            $filtres['du'],
            $filtres['au']
        );

        return $this->render('perception/rapport/organisme/index.html.twig', [
            'page_title' => 'Rapport des prises en charge par organisme',
            'organismes' => $organismeRepository->findBy(['actif' => true], ['nom' => 'ASC']),
            'lignes' => $rapport['lignes'],
            'journal' => $rapport['journal'],
            'totalMontantPec' => $rapport['totalMontantPec'],
            'totalFactures' => $rapport['totalFactures'],
            'totalOrganismes' => $rapport['totalOrganismes'],
            'filtres' => $filtres,
        ]);
    }

    #[Route('/export/pdf', name: 'app_perception_rapport_organisme_export_pdf', methods: ['GET'])]
    public function exportPdf(
        Request $request,
        FacturePECRepository $factureRepository
    ): Response {
        $filtres = $this->extractFiltres($request);

        $rapport = $factureRepository->getRapportPrisesEnChargeParOrganisme(
            $filtres['search'],
            $filtres['organisme'],
            $filtres['du'],
            $filtres['au']
        );

        $html = $this->renderView('perception/rapport/organisme/print.html.twig', [
            'title' => 'Rapport des prises en charge par organisme',
            'lignes' => $rapport['lignes'],
            'journal' => $rapport['journal'],
            'totalMontantPec' => $rapport['totalMontantPec'],
            'totalFactures' => $rapport['totalFactures'],
            'totalOrganismes' => $rapport['totalOrganismes'],
            'filtres' => $filtres,
            'is_pdf' => true,
            'is_print' => false,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="rapport_prises_en_charge_organismes.pdf"',
            ]
        );
    }

    #[Route('/export/excel', name: 'app_perception_rapport_organisme_export_excel', methods: ['GET'])]
    public function exportExcel(
        Request $request,
        FacturePECRepository $factureRepository
    ): StreamedResponse {
        $filtres = $this->extractFiltres($request);

        $rapport = $factureRepository->getRapportPrisesEnChargeParOrganisme(
            $filtres['search'],
            $filtres['organisme'],
            $filtres['du'],
            $filtres['au']
        );

        $response = new StreamedResponse(function () use ($rapport) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Feuille 1 logique : synthèse
            fputcsv($handle, ['RAPPORT DES PRISES EN CHARGE PAR ORGANISME'], ';');
            fputcsv($handle, []);
            fputcsv($handle, ['Organisme', 'Code', 'Nombre de factures', 'Montant brut', 'Montant PEC', 'Montant patient'], ';');

            foreach ($rapport['lignes'] as $ligne) {
                fputcsv($handle, [
                    $ligne['organisme'],
                    $ligne['code'],
                    $ligne['nombre_factures'],
                    $ligne['montant_total_brut'],
                    $ligne['montant_total_pec'],
                    $ligne['montant_total_patient'],
                ], ';');
            }

            fputcsv($handle, []);
            fputcsv($handle, ['JOURNAL DETAILLE'], ';');
            fputcsv($handle, []);
            fputcsv($handle, [
                'Facture',
                'Date émission',
                'Date paiement',
                'Patient',
                'Code patient',
                'Dossier',
                'Organisme',
                'Code organisme',
                'Montant brut',
                'Montant PEC',
                'Montant patient',
                'Payé patient',
                'Reste patient',
                'Statut',
            ], ';');

            foreach ($rapport['journal'] as $item) {
                fputcsv($handle, [
                    '#' . $item['facture_id'],
                    $item['date_emission'] ? $item['date_emission']->format('Y-m-d H:i') : '',
                    $item['date_paiement'] ? $item['date_paiement']->format('Y-m-d H:i') : '',
                    $item['patient'],
                    $item['code_patient'],
                    $item['dossier'],
                    $item['organisme'],
                    $item['code_organisme'],
                    $item['montant_brut'],
                    $item['montant_pec'],
                    $item['montant_patient'],
                    $item['montant_paye_patient'],
                    $item['reste_patient'],
                    $item['statut'],
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="rapport_prises_en_charge_organismes.csv"'
        );

        return $response;
    }

    #[Route('/print', name: 'app_perception_rapport_organisme_print', methods: ['GET'])]
    public function print(
        Request $request,
        FacturePECRepository $factureRepository
    ): Response {
        $filtres = $this->extractFiltres($request);

        $rapport = $factureRepository->getRapportPrisesEnChargeParOrganisme(
            $filtres['search'],
            $filtres['organisme'],
            $filtres['du'],
            $filtres['au']
        );

        return $this->render('perception/rapport/organisme/print.html.twig', [
            'title' => 'Rapport des prises en charge par organisme',
            'lignes' => $rapport['lignes'],
            'journal' => $rapport['journal'],
            'totalMontantPec' => $rapport['totalMontantPec'],
            'totalFactures' => $rapport['totalFactures'],
            'totalOrganismes' => $rapport['totalOrganismes'],
            'filtres' => $filtres,
            'is_pdf' => false,
            'is_print' => true,
        ]);
    }

    private function extractFiltres(Request $request): array
    {
        $organisme = $request->query->get('organisme');
        $organismeId = null;

        if ($organisme !== null && $organisme !== '') {
            $organismeId = (int) $organisme;
        }

        return [
            'search' => trim((string) $request->query->get('search', '')),
            'organisme' => $organismeId,
            'du' => $request->query->get('du') ?: null,
            'au' => $request->query->get('au') ?: null,
        ];
    }
}