<?php

namespace App\Controller;

use App\Entity\Facture;
use App\Entity\OrganismePriseEnCharge;
use App\Entity\Paiement;
use App\Entity\Utilisateur;
use App\Form\EncaissementType;
use App\Repository\FactureRepository;
use App\Repository\PaiementRepository;
use App\Repository\UtilisateurRepository;
use App\Service\FacturationService;
use App\Service\PharmacyManager; // 👈 IMPORT DU NOUVEAU SERVICE
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/perception')]
final class PerceptionController extends AbstractController
{

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_PERCEPTION')"))]
    #[Route('/facture/{id}/toggle-pec', name: 'app_perception_toggle_pec', methods: ['POST'])]
    public function togglePriseEnCharge(
        Facture $facture,
        Request $request,
        FacturationService $facturationService,
        EntityManagerInterface $em
    ): JsonResponse {
        // ... (votre code existant reste inchangé)
        try {
            $data = json_decode($request->getContent(), true);
            $active = (bool) ($data['active'] ?? false);
            $organismeId = $data['organismeId'] ?? null;
            $taux = $data['taux'] ?? null;

            $facture->setPriseEnChargeActive($active);
            if ($active) {
                $facture->setPriseEnChargeManuelle(true);
                if ($organismeId) {
                    $organisme = $em->getRepository(OrganismePriseEnCharge::class)->find($organismeId);
                    $facture->setOrganismePriseEnCharge($organisme);
                }
                if ($taux !== null) {
                    $facture->setTauxPriseEnChargeManuel((int) $taux);
                }
            } else {
                $facture->setPriseEnChargeManuelle(false);
                $facture->setTauxPriseEnChargeManuel(null);
            }

            $facturationService->rafraichirPrixUnitairesSelonPEC($facture);
            $facturationService->recalculerFacture($facture);
            $em->flush();

            return $this->json([
                'montantTotalBrut' => $facture->getMontantTotalBrut(),
                'montantTotalPriseEnCharge' => $facture->getMontantTotalPriseEnCharge(),
                'montantTotalPatient' => $facture->getMontantTotalPatient(),
                'montantPayePatient' => $facture->getMontantPayePatient(),
                'restePatient' => $facture->getRestePatient(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_PERCEPTION')"))]
    #[Route('', name: 'app_perception_index', methods: ['GET'])]
    public function index(Request $request, FactureRepository $factureRepository, PaiementRepository $paiementRepository): Response
    {
        // ... (votre code existant reste inchangé) ...
        $search = trim((string) $request->query->get('search', ''));
        $periodeFilter = $request->query->get('periode');
        if ($periodeFilter === null) {
            $periodeFilter = 'recent';
        }

        $qb = $factureRepository->createQueryBuilder('f')
            ->leftJoin('f.consultation', 'c')->addSelect('c')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('p.dossierMedical', 'dm')->addSelect('dm')
            ->leftJoin('f.paiements', 'pa')->addSelect('pa');

        if ($periodeFilter === 'recent') {
            $date = new \DateTimeImmutable('-30 days');
            $qb->andWhere('f.createdAt >= :date')->setParameter('date', $date);
        } elseif ($periodeFilter === 'month') {
            $now = new \DateTimeImmutable();
            $startOfMonth = $now->setDate($now->format('Y'), $now->format('m'), 1)->setTime(0, 0, 0);
            $qb->andWhere('f.createdAt >= :date')->setParameter('date', $startOfMonth);
        } elseif ($periodeFilter === 'quarter') {
            $date = new \DateTimeImmutable('-90 days');
            $qb->andWhere('f.createdAt >= :date')->setParameter('date', $date);
        } elseif ($periodeFilter === 'year') {
            $date = new \DateTimeImmutable('-365 days');
            $qb->andWhere('f.createdAt >= :date')->setParameter('date', $date);
        }

        if ($search !== '') {
            $qb->andWhere('LOWER(p.code) LIKE :search OR LOWER(p.telephone) LIKE :search OR LOWER(dm.numeroDossier) LIKE :search')
              ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $factures = $qb->orderBy('f.createdAt', 'DESC')->getQuery()->getResult();
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        $paiementsDuJour = $paiementRepository->createQueryBuilder('pa')
            ->select('COALESCE(SUM(pa.montant), 0)')
            ->andWhere('pa.payeLe >= :today')
            ->andWhere('pa.payeLe < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        $nbNonPayees = $nbPartielles = $nbPayees = 0;
        $totalEncaisseJour = (int) $paiementsDuJour;

        foreach ($factures as $facture) {
            $statut = $facture->getStatut()->value;
            if ($statut === 'non_paye') $nbNonPayees++;
            elseif ($statut === 'partiellement_paye') $nbPartielles++;
            elseif ($statut === 'paye') $nbPayees++;
        }

        return $this->render('perception/index.html.twig', [
            'factures' => $factures, 'nbNonPayees' => $nbNonPayees, 'nbPartielles' => $nbPartielles,
            'nbPayees' => $nbPayees, 'totalEncaisseJour' => $totalEncaisseJour,
            'search' => $search, 'periodeFilter' => $periodeFilter,
        ]);
    }

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_PERCEPTION')"))]
    #[Route('/rapport-agents', name: 'app_perception_rapport_agents', methods: ['GET'])]
    public function rapportAgents(Request $request, PaiementRepository $paiementRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        return $this->render('perception/rapport_agents.html.twig', $this->buildEncaissementReportData($request, $paiementRepository, $utilisateurRepository));
    }

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_PERCEPTION')"))]
    #[Route('/rapport-agents/pdf', name: 'app_perception_rapport_agents_pdf', methods: ['GET'])]
    public function rapportAgentsPdf(Request $request, PaiementRepository $paiementRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        // ... (votre code existant PDF reste inchangé) ...
        $data = $this->buildEncaissementReportData($request, $paiementRepository, $utilisateurRepository);
        $html = $this->renderView('perception/rapport_agents_pdf.html.twig', array_merge($data, [
            'generatedAt' => new \DateTimeImmutable(),
            'logo_path' => $this->getEmbeddedLogo(),
        ]));
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rapport-perception.pdf"',
        ]);
    }

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_PERCEPTION')"))]
    #[Route('/facture/{id}', name: 'app_perception_facture_show', methods: ['GET'])]
    public function show(Facture $facture): Response
    {
        return $this->render('perception/show.html.twig', ['facture' => $facture]);
    }

    // =================================================================================
    // LA MÉTHODE ENCAISSER AVEC L'INTÉGRATION DU PHARMACY MANAGER (PUI)
    // =================================================================================
    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_PERCEPTION')"))]
    #[Route('/facture/{id}/encaisser', name: 'app_perception_facture_encaisser', methods: ['GET', 'POST'])]
    public function encaisser(
        Request $request,
        Facture $facture,
        FacturationService $facturationService,
        EntityManagerInterface $em,
        PharmacyManager $pharmacyManager // 👈 INJECTION DU SERVICE ICI
    ): Response {
        
        // On stocke le statut AVANT paiement pour s'assurer de ne pas déduire le stock en double
        $statutAvantPaiement = $facture->getStatut();

        $facturationService->recalculerFacture($facture);

        $form = $this->createForm(EncaissementType::class, $facture, [
            'action' => $this->generateUrl('app_perception_facture_encaisser', ['id' => $facture->getId()]),
            'method' => 'POST',
            'max_amount' => 999999999,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($facture->isPriseEnChargeActive()) {
                if (!$facture->getOrganismePriseEnCharge()) {
                    $form->get('organismePriseEnCharge')->addError(new FormError('Veuillez sélectionner un organisme.'));
                }
                if ($facture->getTauxPriseEnChargeManuel() === null) {
                    $form->get('tauxPriseEnChargeManuel')->addError(new FormError('Veuillez sélectionner un taux de prise en charge.'));
                }
                $facture->setPriseEnChargeManuelle(true);
            } else {
                $facture->setPriseEnChargeManuelle(false);
                $facture->setTauxPriseEnChargeManuel(null);
                $facture->setOrganismePriseEnCharge(null);
            }

            $facturationService->recalculerFacture($facture);
            $resteApresPec = $facture->getRestePatient();

            $montant = (int) ($form->get('montant')->getData() ?? 0);
            $mode = $form->get('mode')->getData();

            if ($resteApresPec > 0) {
                if ($montant <= 0) {
                    $form->get('montant')->addError(new FormError('Le montant doit être supérieur à zéro.'));
                } elseif ($montant > $resteApresPec) {
                    $form->get('montant')->addError(new FormError(sprintf(
                        'Le montant saisi (%s FCFA) dépasse le reste à payer (%s FCFA).',
                        number_format($montant, 0, ',', ' '), number_format($resteApresPec, 0, ',', ' ')
                    )));
                }
                if ($mode === null) {
                    $form->get('mode')->addError(new FormError('Veuillez choisir un mode de paiement.'));
                }
            } else {
                $montant = 0;
            }

            if ($form->isValid()) {
                $currentUser = $this->getUser();
                $effectuePar = $currentUser instanceof Utilisateur ? $currentUser : null;

                try {
                    if ($montant > 0) {
                        $facturationService->ajouterPaiement($facture, $montant, $mode, $effectuePar);
                    } else {
                        $facturationService->recalculerFacture($facture);
                        // Force la mise à jour des statuts si la facture tombe à "Payée" grâce à une PEC de 100%
                        $facturationService->mettreAJourStatutPrestations($facture); 
                    }

                    // ---------------------------------------------------------------------------------
                    // 🚀 DÉDUCTION AUTOMATIQUE DU STOCK (RÈGLE FEFO)
                    // ---------------------------------------------------------------------------------
                    // Si la facture vient TOUT JUSTE de passer au statut "Payé" (complètement réglée)
                    if ($facture->getStatut() === \App\Enum\StatutFacture::PAYE && $statutAvantPaiement !== \App\Enum\StatutFacture::PAYE) {
                        
                        foreach ($facture->getLignes() as $ligne) {
                            $prescription = $ligne->getPrescriptionPrestation();
                            
                            // On vérifie si la ligne facturée est un consommable de pharmacie
                            if ($prescription && mb_strtoupper((string) $prescription->getCategorieLabel()) === 'CONSOMMABLE') {
                                
                                $medicament = null;
                                // Récupération du médicament (selon comment vous avez fait la relation)
                                if (method_exists($prescription, 'getMedicament')) {
                                    $medicament = $prescription->getMedicament();
                                } elseif ($prescription->getTarifPrestation() && method_exists($prescription->getTarifPrestation(), 'getMedicament')) {
                                    $medicament = $prescription->getTarifPrestation()->getMedicament();
                                }

                                if ($medicament) {
                                    // Déclenchement silencieux de la sortie !
                                    $pharmacyManager->deduireConsommationPatient(
                                        $medicament,
                                        $ligne->getQuantite(),
                                        'FACTURE-' . $facture->getId(),
                                        $effectuePar ? $effectuePar->getNomComplet() : 'Perception'
                                    );
                                }
                            }
                        }
                    }
                    // ---------------------------------------------------------------------------------

                } catch (\InvalidArgumentException $exception) {
                    $form->get('montant')->addError(new FormError($exception->getMessage()));
                }

                if ($form->isValid()) {
                    $em->flush();

                    if ($request->isXmlHttpRequest()) {
                        return $this->json([
                            'success' => true,
                            'message' => 'Encaissement enregistré et stock mis à jour si nécessaire.',
                            'printUrl' => $this->generateUrl('app_perception_facture_print', ['id' => $facture->getId()]),
                        ]);
                    }

                    return $this->redirectToRoute('app_perception_facture_show', ['id' => $facture->getId()]);
                }
            }
        }

        return $this->render('perception/_encaissement_form.html.twig', [
            'form' => $form->createView(),
            'facture' => $facture,
        ]);
    }

    // ... (Le reste de votre code avec print, printPdf, etc. reste inchangé)
    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_PERCEPTION')"))]
    #[Route('/facture/{id}/print', name: 'app_perception_facture_print', methods: ['GET'])]
    public function print(Facture $facture): Response
    {
        // (Identique à l'existant)
        $verifyUrl = $this->generateUrl('app_facture_print', ['id' => $facture->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $qrCode = new QrCode(data: $verifyUrl, encoding: new Encoding('UTF-8'), size: 240, margin: 8);
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

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_PERCEPTION')"))]
    #[Route('/facture/{id}/pdf', name: 'app_perception_facture_pdf', methods: ['GET'])]
    public function printPdf(Facture $facture): Response
    {
        // (Identique à l'existant)
        $verifyUrl = $this->generateUrl('app_facture_print', ['id' => $facture->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $qrCode = new QrCode(data: $verifyUrl, encoding: new Encoding('UTF-8'), size: 240, margin: 8);
        $png = (new PngWriter())->write($qrCode)->getString();
        $dataUri = 'data:image/png;base64,' . base64_encode($png);
        $code = 'FAC-' . $facture->getId();
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo.jpeg';
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));

        $html = $this->renderView('perception/print.html.twig', [
            'facture' => $facture, 'qr_data' => $dataUri, 'code_qr' => $code,
            'verifyUrl' => $verifyUrl, 'consultation' => $facture->getConsultation(), 'logo_path' => $logoBase64,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
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

            return new Response($fpdi->Output('S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="facture-%d.pdf"', $facture->getId()),
            ]);
        }

        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="facture-%d.pdf"', $facture->getId()),
        ]);
    }

    private function parseDateFilter(string $value, bool $endOfDay): ?\DateTimeImmutable
    {
        // (Identique à l'existant)
        if ($value === '') return null;
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$date instanceof \DateTimeImmutable) return null;
        return $endOfDay ? $date->setTime(23, 59, 59) : $date->setTime(0, 0, 0);
    }

    private function getEmbeddedLogo(): ?string
    {
        // (Identique à l'existant)
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo.jpeg';
        if (!file_exists($logoPath)) return null;
        return 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents($logoPath));
    }

    private function buildEncaissementReportData(
        Request $request,
        PaiementRepository $paiementRepository,
        UtilisateurRepository $utilisateurRepository
    ): array {
        // (Le code de génération de données du rapport reste complètement inchangé)
        $search = trim((string) $request->query->get('search', ''));
        $agentId = max(0, (int) $request->query->get('agent', 0));
        $dateDebutInput = trim((string) $request->query->get('dateDebut', ''));
        $dateFinInput = trim((string) $request->query->get('dateFin', ''));
        $dateDebut = $this->parseDateFilter($dateDebutInput, false);
        $dateFin = $this->parseDateFilter($dateFinInput, true);
        $currentUser = $this->getUser();
        $connectedUser = $currentUser instanceof Utilisateur ? $currentUser : null;
        $agentFilterLocked = !$this->isGranted('ROLE_ADMIN') && $connectedUser instanceof Utilisateur;

        if ($agentFilterLocked) {
            $agentId = $connectedUser->getId() ?? 0;
        }

        $paiementQb = $paiementRepository->createQueryBuilder('pa')
            ->leftJoin('pa.facture', 'f')->addSelect('f')
            ->leftJoin('pa.effectuePar', 'agent')->addSelect('agent')
            ->leftJoin('f.consultation', 'c')->addSelect('c')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('c.dossierMedical', 'cdm')->addSelect('cdm')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('cdm.patient', 'dmp')->addSelect('dmp')
            ->leftJoin('p.dossierMedical', 'pdm')->addSelect('pdm');

        if ($search !== '') {
            $paiementQb->andWhere(
                'LOWER(COALESCE(p.code, dmp.code, \'\')) LIKE :search
                OR LOWER(COALESCE(p.telephone, dmp.telephone, \'\')) LIKE :search
                OR LOWER(COALESCE(pdm.numeroDossier, cdm.numeroDossier, \'\')) LIKE :search
                OR LOWER(CONCAT(COALESCE(p.nom, dmp.nom, \'\'), \' \', COALESCE(p.prenom, dmp.prenom, \'\'))) LIKE :search'
            )
            ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($agentId > 0) {
            $paiementQb->andWhere('agent.id = :agentId')
                ->setParameter('agentId', $agentId);
        }

        if ($dateDebut instanceof \DateTimeImmutable) {
            $paiementQb->andWhere('pa.payeLe >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin instanceof \DateTimeImmutable) {
            $paiementQb->andWhere('pa.payeLe <= :dateFin')
                ->setParameter('dateFin', $dateFin);
        }

        $paiements = $paiementQb->orderBy('pa.payeLe', 'DESC')
            ->getQuery()
            ->getResult();

        $totalEncaisseFiltre = 0;
        $rapportAgents = [];

        foreach ($paiements as $paiement) {
            $totalEncaisseFiltre += $paiement->getMontant();

            $agent = $paiement->getEffectuePar();
            $agentKey = $agent?->getId() !== null ? (string) $agent->getId() : 'inconnu';

            if (!isset($rapportAgents[$agentKey])) {
                $rapportAgents[$agentKey] = [
                    'agent' => $agent,
                    'libelle' => $agent instanceof Utilisateur ? $agent->getNomComplet() : 'Compte non renseigné',
                    'username' => $agent instanceof Utilisateur ? $agent->getUserIdentifier() : '-',
                    'nombrePaiements' => 0,
                    'montantTotal' => 0,
                ];
            }

            $rapportAgents[$agentKey]['nombrePaiements']++;
            $rapportAgents[$agentKey]['montantTotal'] += $paiement->getMontant();
        }

        $rapportAgents = array_values($rapportAgents);
        usort(
            $rapportAgents,
            static fn (array $gauche, array $droite) => $droite['montantTotal'] <=> $gauche['montantTotal']
        );

        return [
            'paiements' => $paiements,
            'agents' => $agentFilterLocked && $connectedUser instanceof Utilisateur
                ? [$connectedUser]
                : $utilisateurRepository->findUsersByRoles(['ROLE_PERCEPTION']),
            'rapportAgents' => $rapportAgents,
            'agentFilterLocked' => $agentFilterLocked,
            'selectedAgentId' => $agentId,
            'dateDebut' => $dateDebutInput,
            'dateFin' => $dateFinInput,
            'totalEncaisseFiltre' => $totalEncaisseFiltre,
            'nbPaiements' => count($paiements),
            'nbAgentsActifs' => count($rapportAgents),
            'search' => $search,
        ];
    }
}