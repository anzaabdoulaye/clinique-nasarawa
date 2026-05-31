<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use setasign\Fpdi\Fpdi;
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
    public function stock(Request $request, EntityManagerInterface $em): Response 
    {
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

    // =========================================================================
    // NOUVEAU : RAPPORTS DE CONSOMMATION (Remplace les ventes)
    // =========================================================================

    #[Route('/consommations', name: 'app_rapport_pharmacie_consommations', methods: ['GET'])]
    public function consommations(Request $request, EntityManagerInterface $em): Response 
    {
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');

        if (!$dateDebut || !$dateFin) {
            return $this->render('pharmacie/rapport/consommations.html.twig', [
                'resultats' => null,
                'totaux' => null,
                'date_debut' => null,
                'date_fin' => null,
            ]);
        }

        $debut = new \DateTimeImmutable($dateDebut . ' 00:00:00');
        $fin = new \DateTimeImmutable($dateFin . ' 23:59:59');

        $data = $this->getConsommationsReport($em, $debut, $fin);

        return $this->render('pharmacie/rapport/consommations.html.twig', [
            'resultats' => $data['consommations'],
            'totaux' => $data['totaux'],
            'date_debut' => $debut,
            'date_fin' => $fin,
        ]);
    }

    #[Route('/consommations/pdf', name: 'app_rapport_pharmacie_consommations_pdf', methods: ['GET'])]
    public function consommationsPdf(Request $request, EntityManagerInterface $em): Response 
    {
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');

        if (!$dateDebut || !$dateFin) {
            return $this->redirectToRoute('app_rapport_pharmacie_consommations');
        }

        $debut = new \DateTimeImmutable($dateDebut . ' 00:00:00');
        $fin = new \DateTimeImmutable($dateFin . ' 23:59:59');

        $data = $this->getConsommationsReport($em, $debut, $fin);

        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo.jpeg';
        $logoBase64 = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : null;

        $html = $this->renderView('pharmacie/rapport/consommations_pdf.html.twig', [
            'resultats' => $data['consommations'],
            'totaux' => $data['totaux'],
            'date_debut' => $debut,
            'date_fin' => $fin,
            'logo_path' => $logoBase64,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rapport-consommations.pdf"',
        ]);
    }

    // =========================================================================
    // REQUÊTES SQL NATIVES ADAPTÉES AUX MOUVEMENTS DE STOCK
    // =========================================================================

    private function getStockReport(EntityManagerInterface $em, \DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        $conn = $em->getConnection();
        // On interroge désormais mouvement_stock pour les entrées et les sorties
        $sql = "
            SELECT
                m.id AS medicament_id,
                m.nom AS medicament_nom,
                m.sku,
                m.prix_unitaire,
                COALESCE((SELECT SUM(l.quantite) FROM lot l WHERE l.medicament_id = m.id), 0) AS stock_actuel,
                (SELECT MIN(l.date_peremption) FROM lot l WHERE l.medicament_id = m.id AND l.quantite > 0) AS prochaine_peremption,
                
                COALESCE(
                    (SELECT SUM(ms.quantite)
                     FROM mouvement_stock ms
                     WHERE ms.medicament_id = m.id
                       AND ms.type IN ('entree_achat', 'entree_retour')
                       AND ms.created_at BETWEEN :debut AND :fin),
                0) AS entrees_periode,
                
                COALESCE(
                    (SELECT ABS(SUM(ms.quantite))
                     FROM mouvement_stock ms
                     WHERE ms.medicament_id = m.id
                       AND ms.type IN ('sortie_patient', 'sortie_service', 'sortie_perte', 'sortie_ajustement')
                       AND ms.created_at BETWEEN :debut AND :fin),
                0) AS sorties_periode
            FROM medicament m
            WHERE m.actif = true
            ORDER BY m.nom ASC
        ";

        return $conn->fetchAllAssociative($sql, [
            'debut' => $debut->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
        ]);
    }

    private function getConsommationsReport(EntityManagerInterface $em, \DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        $conn = $em->getConnection();

        // Détail des consommations par médicament basé sur le prix d'achat
        $sql = "
            SELECT
                m.id AS medicament_id,
                m.nom AS medicament_nom,
                m.sku,
                ABS(SUM(ms.quantite)) AS quantite_consommee,
                ABS(SUM(ms.quantite * ms.valeur_achat_unitaire)) AS valeur_totale,
                COUNT(ms.id) AS nb_mouvements
            FROM mouvement_stock ms
            JOIN medicament m ON ms.medicament_id = m.id
            WHERE ms.type IN ('sortie_patient', 'sortie_service', 'sortie_perte')
              AND ms.created_at BETWEEN :debut AND :fin
            GROUP BY m.id, m.nom, m.sku
            ORDER BY valeur_totale DESC
        ";

        $consommations = $conn->fetchAllAssociative($sql, [
            'debut' => $debut->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
        ]);

        // Totaux globaux
        $sqlTotaux = "
            SELECT
                COUNT(ms.id) AS nb_mouvements,
                COALESCE(ABS(SUM(ms.quantite)), 0) AS total_quantite,
                COALESCE(ABS(SUM(ms.quantite * ms.valeur_achat_unitaire)), 0) AS total_valeur
            FROM mouvement_stock ms
            WHERE ms.type IN ('sortie_patient', 'sortie_service', 'sortie_perte')
              AND ms.created_at BETWEEN :debut AND :fin
        ";

        $totaux = $conn->fetchAssociative($sqlTotaux, [
            'debut' => $debut->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
        ]);

        return [
            'consommations' => $consommations,
            'totaux' => $totaux,
        ];
    }
}