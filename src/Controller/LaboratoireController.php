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
        // Récupération de toutes les prestations de laboratoire payées, en cours ou réalisées
        $prestations = $repository->findAll(); // ou votre méthode de récupération habituelle

        $aTraiter = [];
        $enCours = [];
        $realises = [];

        foreach ($prestations as $prestation) {
            $consultation = $prestation->getConsultation();
            if (!$consultation) {
                continue;
            }

            $consultationId = $consultation->getId();
            $statut = $prestation->getStatut();

            // Structure de regroupement pour le template _table.html.twig
            $itemData = [
                'consultation' => $consultation,
                'patient' => $this->resolvePatientFromConsultation($consultation),
                'medecin' => $consultation->getMedecin(),
                'prestations' => [$prestation],
                'nombreExamens' => 1,
            ];

            // CLASSIFICATION TECHNIQUE PAR STATUT (Élimine le problème du trou noir)
            if ($statut === StatutPrescriptionPrestation::PAYE) {
                if (!isset($aTraiter[$consultationId])) {
                    $aTraiter[$consultationId] = $itemData;
                } else {
                    $aTraiter[$consultationId]['prestations'][] = $prestation;
                }
            } elseif ($statut === StatutPrescriptionPrestation::EN_COURS) {
                if (!isset($enCours[$consultationId])) {
                    $enCours[$consultationId] = $itemData;
                } else {
                    $enCours[$consultationId]['prestations'][] = $prestation;
                }
            } elseif ($statut === StatutPrescriptionPrestation::REALISE) {
                // On se base uniquement sur le statut REALISE pour l'onglet Traités
                if (!isset($realises[$consultationId])) {
                    $realises[$consultationId] = $itemData;
                } else {
                    $realises[$consultationId]['prestations'][] = $prestation;
                }
            }
        }

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
    public function show(
        PrescriptionPrestation $prestation,
        PrescriptionPrestationRepository $repository
    ): Response
    {
        $this->verifierDestinationLaboratoire($prestation);

        $resultat = $prestation->getResultatLaboratoire();
        $hasResultatSaisi = $resultat ? $this->hasSaisiResultat($resultat) : false;
        $consultation = $prestation->getConsultation();
        $prestationsAvecResultatsConsultation = $consultation instanceof Consultation
            ? $this->getPrestationsAvecResultatsSaisis($consultation, $repository)
            : [];

        return $this->render('laboratoire/show.html.twig', [
            'prestation' => $prestation,
            'canEditResult' => $this->canEditResult($prestation),
            'hasResultatSaisi' => $hasResultatSaisi,
            'canMarkRealise' => $prestation->getStatut() === StatutPrescriptionPrestation::EN_COURS && $hasResultatSaisi,
            'canViewConsultationResultsSheet' => count($prestationsAvecResultatsConsultation) > 0,
            'consultationResultsCount' => count($prestationsAvecResultatsConsultation),
        ]);
    }


    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO') or is_granted('ROLE_MEDECIN')"
))]
#[Route(
    '/consultation/{id}',
    name: 'app_laboratoire_consultation_show',
    methods: ['GET']
)]
public function consultationShow(
    Consultation $consultation,
    PrescriptionPrestationRepository $repository    
): Response {

    /**
     * Tous les examens labo de la consultation
     */
    $prestations = $repository->findExamensLaboPayesParConsultation(
        $consultation->getId()
    );

    if (empty($prestations)) {

        $this->addFlash(
            'warning',
            'Aucun examen laboratoire trouvé pour cette consultation.'
        );

        return $this->redirectToRoute('app_laboratoire_index');
    }

    /**
     * Patient
     */
    $patient = $consultation->getDossierMedical()?->getPatient()
        ?? $consultation->getRendezVous()?->getPatient();

    /**
     * Statistiques
     */
    $total = count($prestations);

    $nombrePayes = 0;
    $nombreEnCours = 0;
    $nombreRealises = 0;

    foreach ($prestations as $prestation) {

        switch ($prestation->getStatut()) {

            case StatutPrescriptionPrestation::PAYE:
                $nombrePayes++;
                break;

            case StatutPrescriptionPrestation::EN_COURS:
                $nombreEnCours++;
                break;

            case StatutPrescriptionPrestation::REALISE:
                $nombreRealises++;
                break;
        }
    }

    /**
     * Progression
     */
    $progression = $total > 0
        ? round(($nombreRealises * 100) / $total)
        : 0;

    return $this->render(
        'laboratoire/consultation_show.html.twig',
        [

            'consultation' => $consultation,

            'patient' => $patient,

            'prestations' => $prestations,

            'total' => $total,

            'nombrePayes' => $nombrePayes,

            'nombreEnCours' => $nombreEnCours,

            'nombreRealises' => $nombreRealises,

            'progression' => $progression,
        ]
    );
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
            $this->addFlash('success', 'Examen reçu et pris en charge avec succès.');
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
        $this->addFlash('success', 'Examen marqué comme réalisé.');

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
    public function bonPrint(Consultation $consultation, PrescriptionPrestationRepository $repository): Response
    {
        $prestations = $repository->findBy(['consultation' => $consultation]);
        $patient = $this->resolvePatientFromConsultation($consultation);

        // Récupération sécurisée de l'âge
        $patientAge = '-';
        if ($patient && method_exists($patient, 'getDateNaissance') && $patient->getDateNaissance()) {
            $patientAge = $patient->getDateNaissance()->diff(new \DateTime())->y;
        }

        $url = $this->generateUrl('app_laboratoire_consultation_show', ['id' => $consultation->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $qrCode = new QrCode($url);
        $writer = new PngWriter();
        $qrCodeBase64 = $writer->write($qrCode)->getDataUri();

        // 1. Définition du chemin physique local vers le dossier public
        $publicPath = $this->getParameter('kernel.project_dir') . '/public';

        $html = $this->renderView('laboratoire/bon_print.html.twig', [
            'consultation' => $consultation,
            'prestations' => $prestations,
            'patient' => $patient,
            'patientAge' => $patientAge,
            'medecinNom' => $consultation->getMedecin()?->getNomComplet() ?? 'Non renseigné',
            'datePrescription' => $consultation->getCreatedAt() ?? new \DateTimeImmutable(),
            'qrCodeBase64' => $qrCodeBase64,
            'publicPath' => $publicPath, // On envoie le chemin au fichier Twig
        ]);

        // 2. Configuration ultra-rapide de Dompdf sans requêtes HTTP virtuelles
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Indispensable pour le QR Code en Base64
        $options->set('chroot', $publicPath);   // Sécurise et autorise l'accès au disque local

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="bon_examen_'.$consultation->getId().'.pdf"'
        ]);
    }

    #[Route('/prestation/{id}/resultat/print', name: 'app_laboratoire_resultat_print', methods: ['GET'])]
    public function resultatPrint(PrescriptionPrestation $prestation): Response
    {
        $resultat = $prestation->getResultatLaboratoire();
        if (!$resultat) {
            $this->addFlash('danger', "Aucun résultat saisi pour cet examen.");
            return $this->redirectToRoute('app_laboratoire_consultation_show', ['id' => $prestation->getConsultation()?->getId()]);
        }

        $consultation = $prestation->getConsultation();
        $patient = $this->resolvePatientFromConsultation($consultation);

        $url = $this->generateUrl('app_laboratoire_consultation_show', ['id' => $consultation->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $qrCode = new QrCode($url);
        $writer = new PngWriter();
        $qrCodeBase64 = $writer->write($qrCode)->getDataUri();

        $publicPath = $this->getParameter('kernel.project_dir') . '/public';

        $html = $this->renderView('laboratoire/resultat_print.html.twig', [
            'prestation' => $prestation,
            'resultat' => $resultat,
            'consultation' => $consultation,
            'patient' => $patient,
            'qrCodeBase64' => $qrCodeBase64,
            'publicPath' => $publicPath, // Injection du chemin
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', $publicPath); // Activation du chroot local

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="resultat_'.$prestation->getId().'.pdf"'
        ]);
    }

    #[Route('/consultation/{id}/resultats/pdf', name: 'app_laboratoire_resultats_consultation_pdf', methods: ['GET'])]
    public function resultatsConsultationPrint(Consultation $consultation, PrescriptionPrestationRepository $repository): Response
    {
        $prestations = $repository->findBy(['consultation' => $consultation]);
        $patient = $this->resolvePatientFromConsultation($consultation);

        $patientAge = '-';
        if ($patient && method_exists($patient, 'getDateNaissance') && $patient->getDateNaissance()) {
            $patientAge = $patient->getDateNaissance()->diff(new \DateTime())->y;
        }

        $url = $this->generateUrl('app_laboratoire_consultation_show', ['id' => $consultation->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $qrCode = new QrCode($url);
        $writer = new PngWriter();
        $qrCodeBase64 = $writer->write($qrCode)->getDataUri();

        $publicPath = $this->getParameter('kernel.project_dir') . '/public';

        $html = $this->renderView('laboratoire/resultats_consultation_print.html.twig', [
            'consultation' => $consultation,
            'prestations' => $prestations,
            'patient' => $patient,
            'patientAge' => $patientAge,
            'qrCodeBase64' => $qrCodeBase64,
            'publicPath' => $publicPath, // Injection du chemin
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', $publicPath); // Activation du chroot local

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="resultats_consultation_'.$consultation->getId().'.pdf"'
        ]);
    }

    /**
     * Marquer tous les examens de la consultation comme terminés / réalisés.
     */
    #[Route('/consultation/{id}/terminer', name: 'app_laboratoire_terminer_consultation', methods: ['POST'])]
    public function terminerConsultation(
        Consultation $consultation, 
        PrescriptionPrestationRepository $repository, 
        EntityManagerInterface $em
    ): Response {
        $prestations = $repository->findBy(['consultation' => $consultation]);
        $count = 0;

        foreach ($prestations as $prestation) {
            // Passe les examens en attente ou en cours au statut Réalisé
            if (in_array($prestation->getStatut(), [StatutPrescriptionPrestation::PAYE, StatutPrescriptionPrestation::EN_COURS], true)) {
                $prestation->setStatut(StatutPrescriptionPrestation::REALISE);
                $count++;
            }

            // Sécurise la traçabilité sur le résultat
            $resultat = $prestation->getResultatLaboratoire();
            if ($resultat) {
                if (!$resultat->getDateValidation()) {
                    $resultat->setDateValidation(new \DateTimeImmutable());
                }
                if (!$resultat->getValidePar()) {
                    // Récupère le nom ou l'identifiant de l'utilisateur connecté
                    $userNom = method_exists($this->getUser(), 'getNom') ? $this->getUser()->getNom() : $this->getUser()?->getUserIdentifier();
                    $resultat->setValidePar($userNom ?? 'Laboratoire');
                }
            }
        }

        $em->flush();
        $this->addFlash('success', 'Le dossier laboratoire de cette consultation a été clôturé et marqué comme terminé.');

        return $this->redirectToRoute('app_laboratoire_consultation_show', ['id' => $consultation->getId()]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO') or is_granted('ROLE_MEDECIN')"
))]
    #[Route('/consultation/{id}/resultats', name: 'app_laboratoire_resultats_consultation_show', methods: ['GET'])]
    public function resultatsConsultationShow(
        Consultation $consultation,
        PrescriptionPrestationRepository $repository
    ): Response {
        $prestations = $this->getPrestationsAvecResultatsSaisis($consultation, $repository);

        if ($prestations === []) {
            $this->addFlash('warning', 'Aucun résultat laboratoire saisi n\'est disponible pour cette consultation.');

            return $this->redirectToRoute('app_laboratoire_bon_show', [
                'id' => $consultation->getId(),
            ]);
        }

        return $this->render('laboratoire/resultats_consultation_show.html.twig', [
            'consultation' => $consultation,
            'prestations' => $prestations,
            'patient' => $this->resolvePatientFromConsultation($consultation),
            'dateValidationReference' => $this->resolveDateValidationReference($prestations),
            'validateurs' => $this->resolveValidateurs($prestations),
        ]);
    }

    #[IsGranted(new Expression(
    "is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO') or is_granted('ROLE_MEDECIN')"
))]
    #[Route('/consultation/{id}/resultats/pdf', name: 'app_laboratoire_resultats_consultation_pdf', methods: ['GET'])]
    public function resultatsConsultationPdf(
        Consultation $consultation,
        PrescriptionPrestationRepository $repository
    ): Response {
        $prestations = $this->getPrestationsAvecResultatsSaisis($consultation, $repository);

        if ($prestations === []) {
            throw $this->createNotFoundException('Aucun résultat laboratoire saisi n\'est disponible pour cette consultation.');
        }

        $verifyUrl = $this->generateUrl('app_laboratoire_resultats_consultation_show', [
            'id' => $consultation->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $qrCode = new QrCode(
            data: $verifyUrl,
            encoding: new Encoding('UTF-8'),
            size: 200,
            margin: 6
        );

        $png = (new PngWriter())->write($qrCode)->getString();
        $dataUri = 'data:image/png;base64,' . base64_encode($png);

        $html = $this->renderView('laboratoire/resultats_consultation_print.html.twig', [
            'consultation' => $consultation,
            'prestations' => $prestations,
            'patient' => $this->resolvePatientFromConsultation($consultation),
            'dateValidationReference' => $this->resolveDateValidationReference($prestations),
            'validateurs' => $this->resolveValidateurs($prestations),
            'qr_data' => $dataUri,
            'code_qr' => 'RC-' . $consultation->getId(),
            'logo_path' => $this->getEmbeddedLogo(),
            'verifyUrl' => $verifyUrl,
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
            'Content-Disposition' => sprintf('inline; filename="resultats_consultation-%d.pdf"', $consultation->getId()),
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

        // --- DEBUT DE L'AJOUT CHIRURGICAL ---
        $libelleExamen = $prestation->getTarifPrestation()?->getLibelle() ?? 'Examen';
        $modeles = $this->getModelesExamen($libelleExamen);
        
        $ordre = 1;
        foreach ($modeles as $modele) {
            $ligne = new ResultatLaboratoireLigne();
            $ligne->setDemande($modele['demande']);
            $ligne->setValeurNormale($modele['norme']);
            $ligne->setOrdre($ordre++);
            $resultat->addLigne($ligne);
        }
        // --- FIN DE L'AJOUT ---
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
            $this->addFlash('success', 'Résultat laboratoire enregistré avec succès.');

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
        $this->addFlash('success', 'Résultat laboratoire enregistré avec succès.');

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
        $consultation = $prestation->getConsultation();
        $patient = $consultation?->getDossierMedical()?->getPatient() ?? $consultation?->getRendezVous()?->getPatient();

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
            'patient' => $patient,
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
        $consultation = $prestation->getConsultation();
        $patient = $consultation?->getDossierMedical()?->getPatient() ?? $consultation?->getRendezVous()?->getPatient();

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
            'patient' => $patient,
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
        $dompdf->setPaper('A4', 'portrait');
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

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO')"))]
    #[Route('/consultation/{id}/tout-prendre-en-charge', name: 'app_laboratoire_tout_prendre_en_charge', methods: ['POST'])]
    public function toutPrendreEnCharge(
        Consultation $consultation,
        PrescriptionPrestationRepository $repository,
        EntityManagerInterface $em
    ): Response {
        // Récupérer tous les examens labo payés de cette consultation
        $prestations = $repository->findExamensLaboPayesParConsultation($consultation->getId());

        $count = 0;
        foreach ($prestations as $prestation) {
            if ($prestation->getStatut() === StatutPrescriptionPrestation::PAYE) {
                $prestation->setStatut(StatutPrescriptionPrestation::EN_COURS);
                $count++;
            }
        }

        if ($count > 0) {
            $em->flush();
            $this->addFlash('success', "$count examen(s) pris en charge avec succès.");
        } else {
            $this->addFlash('info', "Aucun examen en attente de prise en charge.");
        }

        return $this->redirectToRoute('app_laboratoire_consultation_show', [
            'id' => $consultation->getId(),
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

    /**
     * @return PrescriptionPrestation[]
     */
    private function getPrestationsAvecResultatsSaisis(
        Consultation $consultation,
        PrescriptionPrestationRepository $repository
    ): array {
        $prestations = $repository->findExamensLaboAvecResultatsParConsultation($consultation->getId());

        return array_values(array_filter(
            $prestations,
            fn (PrescriptionPrestation $prestation): bool => $prestation->getResultatLaboratoire() instanceof ResultatLaboratoire
                && $this->hasSaisiResultat($prestation->getResultatLaboratoire())
        ));
    }

    private function resolvePatientFromConsultation(Consultation $consultation): ?object
    {
        return $consultation->getDossierMedical()?->getPatient() ?? $consultation->getRendezVous()?->getPatient();
    }

    /**
     * @param PrescriptionPrestation[] $prestations
     */
    private function resolveDateValidationReference(array $prestations): ?\DateTimeImmutable
    {
        $dateReference = null;

        foreach ($prestations as $prestation) {
            $dateValidation = $prestation->getResultatLaboratoire()?->getDateValidation();

            if ($dateValidation !== null && ($dateReference === null || $dateValidation > $dateReference)) {
                $dateReference = $dateValidation;
            }
        }

        return $dateReference;
    }

    /**
     * @param PrescriptionPrestation[] $prestations
     *
     * @return string[]
     */
    private function resolveValidateurs(array $prestations): array
    {
        $validateurs = [];

        foreach ($prestations as $prestation) {
            $validePar = trim((string) $prestation->getResultatLaboratoire()?->getValidePar());

            if ($validePar !== '' && !in_array($validePar, $validateurs, true)) {
                $validateurs[] = $validePar;
            }
        }

        return $validateurs;
    }
/**
     * Retourne les paramètres et valeurs normales pré-définis selon l'examen.
     */
    private function getModelesExamen(string $libelleExamen): array
    {
        // On passe en minuscule pour faciliter la recherche par mots-clés
        $libelle = mb_strtolower(trim($libelleExamen));

        // 1. Numération Formule Sanguine (NFS)
        if (str_contains($libelle, 'nfs') || str_contains($libelle, 'numération') || str_contains($libelle, 'formule sanguine')) {
            return [
                ['demande' => 'Globules Blancs (GB)', 'norme' => '4000 - 10000 /mm3'],
                ['demande' => 'Polynucléaires Neutrophiles', 'norme' => '2000 - 7500 /mm3 (50-70%)'],
                ['demande' => 'Lymphocytes', 'norme' => '1500 - 4000 /mm3 (20-40%)'],
                ['demande' => 'Monocytes', 'norme' => '200 - 1000 /mm3 (2-10%)'],
                ['demande' => 'Polynucléaires Eosinophiles', 'norme' => '40 - 400 /mm3 (1-4%)'],
                ['demande' => 'Polynucléaires Basophiles', 'norme' => '10 - 100 /mm3 (<1%)'],
                ['demande' => 'Globules Rouges (GR)', 'norme' => 'H: 4.5-5.2 | F: 4.0-5.2 Millions/mm3'],
                ['demande' => 'Hémoglobine (Hb)', 'norme' => 'H: 13-17 | F: 12-16 g/dl'],
                ['demande' => 'Hématocrite', 'norme' => 'H: 40-52% | F: 36-47%'],
                ['demande' => 'VGM', 'norme' => '80 - 98 fl'],
                ['demande' => 'TCMH', 'norme' => '27 - 32 pg'],
                ['demande' => 'CCMH', 'norme' => '32 - 36 g/dl'],
                ['demande' => 'Plaquettes', 'norme' => '150 000 - 450 000 /mm3'],
            ];
        }

        // 2. Ionogramme Sanguin
        if (str_contains($libelle, 'ionogramme')) {
            return [
                ['demande' => 'Sodium (Na+)', 'norme' => '135 - 145 mmol/l'],
                ['demande' => 'Potassium (K+)', 'norme' => '3.5 - 5.0 mmol/l'],
                ['demande' => 'Chlore (Cl-)', 'norme' => '98 - 107 mmol/l'],
                ['demande' => 'Bicarbonates (HCO3-)', 'norme' => '22 - 28 mmol/l'],
                ['demande' => 'Calcium', 'norme' => '88 - 104 mg/l'],
                ['demande' => 'Magnésium', 'norme' => '18 - 26 mg/l'],
            ];
        }

        // 3. Bilan Lipidique
        if (str_contains($libelle, 'lipidique') || str_contains($libelle, 'cholestérol total')) {
            return [
                ['demande' => 'Cholestérol total', 'norme' => '1.20 - 2.39 g/l'],
                ['demande' => 'HDL Cholestérol', 'norme' => '> 0.40 g/l (H) | > 0.50 g/l (F)'],
                ['demande' => 'LDL Cholestérol', 'norme' => '0.5 - 1.59 g/l'],
                ['demande' => 'Triglycérides', 'norme' => '0.60 - 1.60 g/l'],
            ];
        }

        // 4. Bilan Hépatique
        if (str_contains($libelle, 'hépatique') || str_contains($libelle, 'transaminase')) {
            return [
                ['demande' => 'ASAT (TGO)', 'norme' => '< 35 UI/l'],
                ['demande' => 'ALAT (TGP)', 'norme' => '< 40 UI/l'],
                ['demande' => 'Gamma GT', 'norme' => '< 45 UI/l'],
                ['demande' => 'Phosphatase alcaline', 'norme' => '30 - 150 UI/l'],
                ['demande' => 'Bilirubine totale', 'norme' => '< 21 µmol/l'],
                ['demande' => 'Bilirubine conjuguée directe', 'norme' => '< 4 µmol/l'],
            ];
        }

        // 5. Hémostase / Coagulation (TP, TCK, INR)
        if (str_contains($libelle, 'hémostase') || str_contains($libelle, 'prothrombine') || str_contains($libelle, 'tca') || str_contains($libelle, 'tck')) {
            return [
                ['demande' => 'Taux de Prothrombine (TP)', 'norme' => '70 - 100 %'],
                ['demande' => 'Temps de Céphaline Activée (TCK/TCA)', 'norme' => '25 - 43 Secondes'],
                ['demande' => 'INR', 'norme' => '0.70 - 1.3'],
            ];
        }

        // 6. ECBU (Examen Cytobactériologique des Urines)
        if (str_contains($libelle, 'ecbu') || str_contains($libelle, 'cytobactériologique des urines')) {
            return [
                ['demande' => 'Aspect (Macroscopie)', 'norme' => 'Clair / Jaune'],
                ['demande' => 'Leucocytes', 'norme' => '< 10 000 /ml'],
                ['demande' => 'Hématies', 'norme' => '< 10 000 /ml'],
                ['demande' => 'Cellules épithéliales', 'norme' => 'Rares ou absentes'],
                ['demande' => 'Cristaux / Cylindres', 'norme' => 'Absents'],
                ['demande' => 'Levures / Parasites', 'norme' => 'Absents'],
                ['demande' => 'Flore bactérienne', 'norme' => 'Absente'],
                ['demande' => 'Numération des germes (Compte de Kass)', 'norme' => '< 10^3 UFC/ml'],
                ['demande' => 'Espèce identifiée', 'norme' => ''],
            ];
        }

        // 7. Bandelette Urinaire
        if (str_contains($libelle, 'bandelette')) {
            return [
                ['demande' => 'Protéine', 'norme' => 'Négatif'],
                ['demande' => 'Glucose', 'norme' => 'Négatif'],
                ['demande' => 'Corps cétoniques', 'norme' => 'Négatif'],
                ['demande' => 'Sang / Hématies', 'norme' => 'Négatif'],
                ['demande' => 'Leucocytes', 'norme' => 'Négatif'],
                ['demande' => 'Nitrites', 'norme' => 'Négatif'],
                ['demande' => 'PH', 'norme' => '5 - 6.5'],
                ['demande' => 'Densité urinaire', 'norme' => '1.003'],
            ];
        }

        // 8. Coproculture / Selles
        if (str_contains($libelle, 'copro') || str_contains($libelle, 'selles')) {
            return [
                ['demande' => 'Aspect / Consistance', 'norme' => 'Moulée'],
                ['demande' => 'Leucocytes', 'norme' => 'Absents'],
                ['demande' => 'Hématies', 'norme' => 'Absentes'],
                ['demande' => 'Levures / Kystes', 'norme' => 'Absents'],
                ['demande' => 'Œufs / Formes végétatives', 'norme' => 'Absents'],
                ['demande' => 'Isolement et identification', 'norme' => 'Flore normale'],
            ];
        }

        // 9. Liquide Céphalo-Rachidien (LCR)
        if (str_contains($libelle, 'lcr') || str_contains($libelle, 'rachidien')) {
            return [
                ['demande' => 'Aspect', 'norme' => 'Eau de roche'],
                ['demande' => 'Leucocytes', 'norme' => '< 5 /mm3'],
                ['demande' => 'Hématies', 'norme' => 'Absentes'],
                ['demande' => 'Glucorachie', 'norme' => '2.5 - 4.4 mmol/l'],
                ['demande' => 'Protéinorachie', 'norme' => '0.15 - 0.45 g/l'],
                ['demande' => 'Chlorure du LCR', 'norme' => '120 - 130 mmol/l'],
            ];
        }

        // 10. Spermogramme
        if (str_contains($libelle, 'spermo')) {
            return [
                ['demande' => 'Volume', 'norme' => '≥ 1.5 ml'],
                ['demande' => 'PH', 'norme' => '≥ 7.2'],
                ['demande' => 'Numération', 'norme' => '20 - 200 millions/ml'],
                ['demande' => 'Mobilité progressive (RP + LP)', 'norme' => '> 32% (ou total > 40%)'],
                ['demande' => 'Vitalité (1ère heure)', 'norme' => '≥ 58%'],
                ['demande' => 'Formes normales', 'norme' => '≥ 4%'],
                ['demande' => 'Leucocytes / Cellules rondes', 'norme' => '< 1 million/ml'],
            ];
        }

        // 11. Widal / Sérologie Typhoïde
        if (str_contains($libelle, 'widal') || str_contains($libelle, 'salmonella')) {
            return [
                ['demande' => 'Antigène O (Typhi)', 'norme' => 'Négatif'],
                ['demande' => 'Antigène H (Typhi)', 'norme' => 'Négatif'],
                ['demande' => 'Paratyphi A', 'norme' => 'Négatif'],
                ['demande' => 'Paratyphi B', 'norme' => 'Négatif'],
            ];
        }

        // 12. Groupage Sanguin
        if (str_contains($libelle, 'groupage')) {
            return [
                ['demande' => 'Groupe sanguin (ABO)', 'norme' => ''],
                ['demande' => 'Rhésus (D)', 'norme' => ''],
            ];
        }

        // --- NOUVEAUX BLOCS À AJOUTER/MODIFIER ---

        // Beta HCG
        if (str_contains($libelle, 'beta hcg') || str_contains($libelle, 'béta hcg') || str_contains($libelle, 'β hcg')) {
            return [['demande' => 'Beta HCG', 'norme' => '< 5 mUI/ml']];
        }

        // Antigène HBs / Hépatite B
        if (str_contains($libelle, 'antigène hbs') || str_contains($libelle, 'ag hbs')) {
            return [['demande' => 'Antigène HBs', 'norme' => 'Négatif']];
        }

        // Bilirubine détaillée (Si c'est prescrit individuellement)
        if (str_contains($libelle, 'bilirubine indirecte')) {
            return [['demande' => 'Bilirubine indirecte', 'norme' => '< 17 µmol/l']];
        }
        if (str_contains($libelle, 'bilirubine directe') || str_contains($libelle, 'conjuguée')) {
            return [['demande' => 'Bilirubine conjuguée directe', 'norme' => '< 4 µmol/l']];
        }
        if (str_contains($libelle, 'bilirubine totale')) {
            return [['demande' => 'Bilirubine totale', 'norme' => '< 21 µmol/l']];
        }

        // ================= TESTS UNIQUES FREQUENTS =================
        if (str_contains($libelle, 'glycémie')) {
            return [['demande' => 'Glucose', 'norme' => '0.60 - 1.10 g/l']];
        }
        if (str_contains($libelle, 'urée')) {
            return [['demande' => 'Urée', 'norme' => '0.15 - 0.45 g/l']];
        }
        if (str_contains($libelle, 'créatinine')) {
            return [['demande' => 'Créatinine', 'norme' => 'H: 7-14 mg/l | F: 6-11 mg/l']];
        }
        if (str_contains($libelle, 'acide urique')) {
            return [['demande' => 'Acide urique', 'norme' => 'H: 35-70 mg/l | F: 20-50 mg/l']];
        }
        if (str_contains($libelle, 'crp')) {
            return [['demande' => 'Protéine C-Réactive (CRP)', 'norme' => '< 6 mg/l']];
        }
        if (str_contains($libelle, 'ferritinémie') || str_contains($libelle, 'ferritine')) {
            return [['demande' => 'Ferritine', 'norme' => 'H: 30-300 ng/ml | F: 20-150 ng/ml']];
        }
        if (str_contains($libelle, 'psa')) {
            return [['demande' => 'PSA', 'norme' => '< 4 ng/ml']];
        }

        // PAR DÉFAUT : Si l'examen n'est pas dans la liste, on crée 1 seule ligne avec le nom de l'examen
        return [
            ['demande' => $libelleExamen, 'norme' => '']
        ];
    }
}