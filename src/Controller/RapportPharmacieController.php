<?php

namespace App\Controller;

use App\Repository\LotRepository;
use App\Repository\MedicamentRepository;
use App\Repository\VenteRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/pharmacie/rapports')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class RapportPharmacieController extends AbstractController
{
    #[Route('', name: 'app_rapport_pharmacie_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('pharmacie/rapport/index.html.twig');
    }

    #[Route('/stock', name: 'app_rapport_pharmacie_stock', methods: ['GET'])]
    public function stock(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');

        if (!$dateDebut || !$dateFin) {
            return $this->render('pharmacie/rapport/stock.html.twig', [
                'resultats' => null,
                'date_debut' => null,
                'date_fin' => null,
            ]);
        }

        $debut = new \DateTimeImmutable($dateDebut . ' 00:00:00');
        $fin = new \DateTimeImmutable($dateFin . ' 23:59:59');

        $resultats = $this->getStockReport($em, $debut, $fin);

        return $this->render('pharmacie/rapport/stock.html.twig', [
            'resultats' => $resultats,
            'date_debut' => $debut,
            'date_fin' => $fin,
        ]);
    }

    // #[Route('/stock/pdf', name: 'app_rapport_pharmacie_stock_pdf', methods: ['GET'])]
    // public function stockPdf(
    //     Request $request,
    //     EntityManagerInterface $em,
    // ): Response {
    //     $dateDebut = $request->query->get('date_debut');
    //     $dateFin = $request->query->get('date_fin');

    //     if (!$dateDebut || !$dateFin) {
    //         return $this->redirectToRoute('app_rapport_pharmacie_stock');
    //     }

    //     $debut = new \DateTimeImmutable($dateDebut . ' 00:00:00');
    //     $fin = new \DateTimeImmutable($dateFin . ' 23:59:59');

    //     $resultats = $this->getStockReport($em, $debut, $fin);

    //     $html = $this->renderView('pharmacie/rapport/stock_pdf.html.twig', [
    //         'resultats' => $resultats,
    //         'date_debut' => $debut,
    //         'date_fin' => $fin,
    //     ]);

    //     return $this->generatePdf($html, 'rapport-stock-' . $dateDebut . '-' . $dateFin . '.pdf');
    // }


    #[Route('/stock/pdf', name: 'app_rapport_pharmacie_stock_pdf', methods: ['GET'])]
    public function stockPdf(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');

        if (!$dateDebut || !$dateFin) {
            return $this->redirectToRoute('app_rapport_pharmacie_stock');
        }

        try {
            $debut = new \DateTimeImmutable($dateDebut . ' 00:00:00');
            $fin = new \DateTimeImmutable($dateFin . ' 23:59:59');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Format de date invalide.');
            return $this->redirectToRoute('app_rapport_pharmacie_stock');
        }

        if ($debut > $fin) {
            $this->addFlash('error', 'La date de début doit être avant la date de fin.');
            return $this->redirectToRoute('app_rapport_pharmacie_stock');
        }

        // 🔹 Données
        $resultats = $this->getStockReport($em, $debut, $fin);

        // 🔹 LOGO
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo.jpeg';
        $logoBase64 = null;

        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
        }

        // 🔹 HTML
        $html = $this->renderView('pharmacie/rapport/stock_pdf.html.twig', [
            'resultats' => $resultats,
            'date_debut' => $debut,
            'date_fin' => $fin,
            'logo_path' => $logoBase64,
        ]);

        // 🔹 DOMPDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // ✅ FORMAT A4
        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();

        $pdfOutput = $dompdf->output();

        // 🔹 (OPTIONNEL) fusion avec annexe
        $extraPath = $this->getParameter('kernel.project_dir') . '/public/pdf/ANNEXE_STOCK.pdf';

        if (file_exists($extraPath)) {
            $temp = sys_get_temp_dir() . '/stock.pdf';
            file_put_contents($temp, $pdfOutput);

            $fpdi = new Fpdi();

            // PDF principal
            $count1 = $fpdi->setSourceFile($temp);
            for ($p = 1; $p <= $count1; $p++) {
                $tpl = $fpdi->importPage($p);
                $size = $fpdi->getTemplateSize($tpl);

                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($tpl);
            }

            // PDF annexe
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
                'Content-Disposition' => 'inline; filename="rapport-stock.pdf"',
            ]);
        }

        // 🔹 Retour simple
        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rapport-stock.pdf"',
        ]);
    }

    #[Route('/ventes', name: 'app_rapport_pharmacie_ventes', methods: ['GET'])]
    public function ventes(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');

        if (!$dateDebut || !$dateFin) {
            return $this->render('pharmacie/rapport/ventes.html.twig', [
                'resultats' => null,
                'totaux' => null,
                'date_debut' => null,
                'date_fin' => null,
            ]);
        }

        $debut = new \DateTimeImmutable($dateDebut . ' 00:00:00');
        $fin = new \DateTimeImmutable($dateFin . ' 23:59:59');

        $data = $this->getVentesReport($em, $debut, $fin);

        return $this->render('pharmacie/rapport/ventes.html.twig', [
            'resultats' => $data['ventes'],
            'totaux' => $data['totaux'],
            'date_debut' => $debut,
            'date_fin' => $fin,
        ]);
    }

    // #[Route('/ventes/pdf', name: 'app_rapport_pharmacie_ventes_pdf', methods: ['GET'])]
    // public function ventesPdf(
    //     Request $request,
    //     EntityManagerInterface $em,
    // ): Response {
    //     $dateDebut = $request->query->get('date_debut');
    //     $dateFin = $request->query->get('date_fin');

    //     if (!$dateDebut || !$dateFin) {
    //         return $this->redirectToRoute('app_rapport_pharmacie_ventes');
    //     }

    //     $debut = new \DateTimeImmutable($dateDebut . ' 00:00:00');
    //     $fin = new \DateTimeImmutable($dateFin . ' 23:59:59');

    //     $data = $this->getVentesReport($em, $debut, $fin);

    //     $html = $this->renderView('pharmacie/rapport/ventes_pdf.html.twig', [
    //         'resultats' => $data['ventes'],
    //         'totaux' => $data['totaux'],
    //         'date_debut' => $debut,
    //         'date_fin' => $fin,
    //     ]);

    //     return $this->generatePdf($html, 'rapport-ventes-' . $dateDebut . '-' . $dateFin . '.pdf');
    // }

    #[Route('/ventes/pdf', name: 'app_rapport_pharmacie_ventes_pdf', methods: ['GET'])]
    public function ventesPdf(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');

        if (!$dateDebut || !$dateFin) {
            return $this->redirectToRoute('app_rapport_pharmacie_ventes');
        }

        // 🔒 Sécurisation des dates
        try {
            $debut = new \DateTimeImmutable($dateDebut . ' 00:00:00');
            $fin = new \DateTimeImmutable($dateFin . ' 23:59:59');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Format de date invalide.');
            return $this->redirectToRoute('app_rapport_pharmacie_ventes');
        }

        if ($debut > $fin) {
            $this->addFlash('error', 'La date de début doit être avant la date de fin.');
            return $this->redirectToRoute('app_rapport_pharmacie_ventes');
        }

        // 🔹 Données
        $data = $this->getVentesReport($em, $debut, $fin);

        // 🔹 LOGO
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo.jpeg';
        $logoBase64 = null;

        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
        }

        // 🔹 HTML
        $html = $this->renderView('pharmacie/rapport/ventes_pdf.html.twig', [
            'resultats' => $data['ventes'],
            'totaux' => $data['totaux'],
            'date_debut' => $debut,
            'date_fin' => $fin,
            'logo_path' => $logoBase64,
        ]);

        // 🔹 DOMPDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // ✅ FORMAT A4
        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();

        $pdfOutput = $dompdf->output();

        // 🔹 (OPTIONNEL) Fusion avec annexe
        $extraPath = $this->getParameter('kernel.project_dir') . '/public/pdf/ANNEXE_VENTES.pdf';

        if (file_exists($extraPath)) {
            $temp = sys_get_temp_dir() . '/ventes.pdf';
            file_put_contents($temp, $pdfOutput);

            $fpdi = new Fpdi();

            // PDF principal
            $count1 = $fpdi->setSourceFile($temp);
            for ($p = 1; $p <= $count1; $p++) {
                $tpl = $fpdi->importPage($p);
                $size = $fpdi->getTemplateSize($tpl);

                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($tpl);
            }

            // PDF annexe
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
                'Content-Disposition' => 'inline; filename="rapport-ventes.pdf"',
            ]);
        }

        // 🔹 Retour simple
        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rapport-ventes.pdf"',
        ]);
    }

    private function getStockReport(EntityManagerInterface $em, \DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        $conn = $em->getConnection();

        // Current stock per medicament with lot details
        $sql = "
            SELECT
                m.id AS medicament_id,
                m.nom AS medicament_nom,
                m.sku,
                m.prix_unitaire,
                COALESCE(SUM(l.quantite), 0) AS stock_actuel,
                COUNT(l.id) AS nb_lots,
                MIN(l.date_peremption) AS prochaine_peremption,
                COALESCE(
                    (SELECT SUM(bml.quantite)
                     FROM bon_matiere_ligne bml
                     JOIN bon_matiere bm ON bml.bon_id = bm.id
                     WHERE bml.medicament_id = m.id
                       AND bm.type = 'ENTREE'
                       AND bm.statut = 'VALIDE'
                       AND bm.date_bon BETWEEN :debut AND :fin),
                0) AS entrees_periode,
                COALESCE(
                    (SELECT SUM(bml.quantite)
                     FROM bon_matiere_ligne bml
                     JOIN bon_matiere bm ON bml.bon_id = bm.id
                     WHERE bml.medicament_id = m.id
                       AND bm.type = 'SORTIE_DEFINITIVE'
                       AND bm.statut = 'VALIDE'
                       AND bm.date_bon BETWEEN :debut AND :fin),
                0) AS sorties_periode
            FROM medicament m
            LEFT JOIN lot l ON l.medicament_id = m.id
            WHERE m.actif = true
            GROUP BY m.id, m.nom, m.sku, m.prix_unitaire
            ORDER BY m.nom ASC
        ";

        return $conn->fetchAllAssociative($sql, [
            'debut' => $debut->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
        ]);
    }

    private function getVentesReport(EntityManagerInterface $em, \DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        $conn = $em->getConnection();

        // Sales details per medicament
        $sql = "
            SELECT
                m.id AS medicament_id,
                m.nom AS medicament_nom,
                m.sku,
                SUM(vl.quantite) AS quantite_vendue,
                SUM(vl.prix_unitaire * vl.quantite) AS montant_total,
                COUNT(DISTINCT v.id) AS nb_ventes
            FROM vente_ligne vl
            JOIN vente v ON vl.vente_id = v.id
            JOIN medicament m ON vl.medicament_id = m.id
            WHERE v.date BETWEEN :debut AND :fin
            GROUP BY m.id, m.nom, m.sku
            ORDER BY montant_total DESC
        ";

        $ventes = $conn->fetchAllAssociative($sql, [
            'debut' => $debut->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
        ]);

        // Totaux
        $sqlTotaux = "
            SELECT
                COUNT(DISTINCT v.id) AS nb_ventes,
                COALESCE(SUM(vl.quantite), 0) AS total_quantite,
                COALESCE(SUM(vl.prix_unitaire * vl.quantite), 0) AS total_montant
            FROM vente v
            JOIN vente_ligne vl ON vl.vente_id = v.id
            WHERE v.date BETWEEN :debut AND :fin
        ";

        $totaux = $conn->fetchAssociative($sqlTotaux, [
            'debut' => $debut->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
        ]);

        return [
            'ventes' => $ventes,
            'totaux' => $totaux,
        ];
    }

    private function generatePdf(string $html, string $filename): Response
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $pdfOutput = $dompdf->output();

        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
