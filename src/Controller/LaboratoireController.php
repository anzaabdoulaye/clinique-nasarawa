<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\PrescriptionPrestation;
use App\Entity\ResultatLaboratoire;
use App\Entity\ResultatLaboratoireLigne;
use App\Entity\Utilisateur;
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
use App\Repository\ResultatLaboratoireRepository;
use App\Repository\UtilisateurRepository;

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

            // --- DEBUT DE L'AJOUT CHIRURGICAL DYNAMIQUE ---
            $consultation = $prestation->getConsultation();
            $patient = $consultation ? $this->resolvePatientFromConsultation($consultation) : null;

            // Extraction sécurisée du sexe (conversion en majuscules pour standardiser l'interprétation)
            $sexe = 'H'; 
            if ($patient && method_exists($patient, 'getSexe') && $patient->getSexe()) {
                $sexe = strtoupper(trim((string) $patient->getSexe()));
            }

            // Calcul de l'âge à la date du jour
            $age = null;
            if ($patient && method_exists($patient, 'getDateNaissance') && $patient->getDateNaissance()) {
                $age = $patient->getDateNaissance()->diff(new \DateTime())->y;
            }

            $libelleExamen = $prestation->getTarifPrestation()?->getLibelle() ?? 'Examen';
            
            // Appel de la nouvelle signature
            $modeles = $this->getModelesExamen($libelleExamen, $sexe, $age);
            
            $ordre = 1;
            foreach ($modeles as $modele) {
                $ligne = new ResultatLaboratoireLigne();
                $ligne->setDemande($modele['demande']);
                $ligne->setValeurNormale($modele['norme']);

                // Prise en charge du groupe s'il est défini dans le modèle
                if (isset($modele['groupe'])) {
                    $ligne->setGroupe($modele['groupe']);
                }
                $ligne->setOrdre($ordre++);
                $resultat->addLigne($ligne);
            }
            // --- FIN DE L'AJOUT CHIRURGICAL DYNAMIQUE ---
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
     * Retourne les paramètres et valeurs normales pré-définis selon l'examen et le profil clinique du patient.
     *
     * @param string $libelleExamen Le nom de l'examen prescrit
     * @param string $sexe 'H' pour Homme, 'F' pour Femme
     * @param int|null $age Âge en années (0 pour les moins d'un an)
     */
    private function getModelesExamen(string $libelleExamen, string $sexe, ?int $age): array
    {
        $libelleInitial = mb_strtolower(trim($libelleExamen));

        // 1. Dictionnaire des alias (Mapping exhaustif Facturation -> Laboratoire)
        $aliasList = [
            'calcémie' => 'calcium',
            'glycémie capillaire' => 'glucose',
            'glycémie veineuse' => 'glucose',
            'glycémie' => 'glucose',
            'glycemie' => 'glucose',
            'œstradiol' => 'estradiol',
            'oestradiol' => 'estradiol',
            'ferritinémie' => 'ferritine',
            'protéinurie de 24h' => 'protéine urinaire',
            'tgo' => 'asat',
            'tgp' => 'alat',
            'transaminase' => 'transaminases',
           // --- NOUVEAUX ALIAS POUR LE CHOLESTEROL ---
            'cholestérolémie' => 'cholestérol total',
            'cholestérol' => 'cholestérol total',
            'cholesterol' => 'cholestérol total',
            'cholestérol ldl' => 'ldl',
            'cholesterol ldl' => 'ldl',
            'cholestérol hdl' => 'hdl',
            'cholesterol hdl' => 'hdl',
            'bilirubine conjuguée directe' => 'bilirubine directe',
            'bilirubine conjuguée' => 'bilirubine directe',
            'crp quantitative' => 'crp',
            'goutte épaisse + densité parasitaire' => 'goutte epaisse',
            'goutte epaisse' => 'goutte epaisse',
            'vitesse de sédimentation (vs)' => 'vs',
            'hémoglobine gluquée' => 'hemoglobine glyquee',
            'hemoglobine glyquee' => 'hemoglobine glyquee',
            'taux de prothrombine' => 'tp',
            'tca' => 'tck',
            'bw (sérologie syphilitique)' => 'bw',
            'hiv' => 'vih',
            'micro albuminémie' => 'micro albuminurie',
            'magnésémie' => 'magnésium',
            't4 libre' => 't4',
            'Selle KOPA' => 'selle kopa',
            'selle kopa' => 'selle kopa',
            'eps' => 'selle kopa',
            'examen parasitologique des selles' => 'selle kopa',
            'parasitologie des selles' => 'selle kopa',
            'coproculture' => 'selle kopa',
        ];

        $libelle = $aliasList[$libelleInitial] ?? $libelleInitial;

        // 2. Détermination du profil du patient
        $profil = 'ADULTE';
        if ($age !== null) {
            if ($age === 0) {
                $profil = 'NOUVEAU_NE';
            } elseif ($age < 15) {
                $profil = 'ENFANT';
            } else {
                $profil = ($sexe === 'F') ? 'FEMME' : 'HOMME';
            }
        } else {
            $profil = ($sexe === 'F') ? 'FEMME' : 'HOMME';
        }

        $isFemme = ($sexe === 'F');

        // =====================================================================
        // 3. BLOCS HORMONAUX & MARQUEURS SPÉCIFIQUES
        // =====================================================================
        
        if (str_contains($libelle, 'estradiol')) {
            $norme = $isFemme 
                ? "Fol: 20-150 Pg/ml | Ov: 150-500 Pg/ml | Lut: 50-250 Pg/ml | Men: < 30 Pg/ml" 
                : "10 - 50 Pg/ml";
            return [['demande' => 'Estradiol', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'fsh')) {
            $norme = $isFemme 
                ? "Fol: 2-13 mUI/ml | Ov: 6-25 mUI/ml | Lut: 1-12 mUI/ml | Men: 25-145 mUI/ml" 
                : "1 - 10 mUI/ml";
            return [['demande' => 'FSH', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'lh')) {
            $norme = $isFemme 
                ? "Fol: 2-11 mUI/ml | Ov: 16-65 mUI/ml | Lut: 1-12 mUI/ml | Men: 18-65 mUI/ml" 
                : "1 - 8.5 mUI/ml";
            return [['demande' => 'LH', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'progestérone') || str_contains($libelle, 'progesterone')) {
            $norme = $isFemme 
                ? "Fol: 0.2-2.1 ng/ml | Ov: 0.7-4.2 ng/ml | Lut: 6.6-28.7 ng/ml | Men: 0.2-0.56 ng/ml" 
                : "< 3 ng/ml";
            return [['demande' => 'Progestérone', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'testostérone') || str_contains($libelle, 'testosterone')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '< 5 ng/ml',
                'ENFANT' => '0.07 - 0.5 ng/ml',
                'FEMME' => '0.2 - 0.6 ng/ml',
                default => '4 - 8 ng/ml',
            };
            return [['demande' => 'Testostérone', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'beta hcg') || str_contains($libelle, 'béta hcg')) {
            $norme = $isFemme ? '< 5 mUI/ml' : '< 2 mUI/ml';
            return [['demande' => 'Beta HCG', 'norme' => $norme]];
        }

        // =====================================================================
        // 4. EXAMENS BIOCHIMIQUES ET ENZYMATIQUES (Avec variations d'âge)
        // =====================================================================

        if (str_contains($libelle, 'acide urique')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '20 - 60 mg/l',
                'ENFANT' => '25 - 60 mg/l',
                'FEMME' => '20 - 50 mg/l',
                default => '35 - 70 mg/l',
            };
            return [['demande' => 'Acide urique', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'urée') || str_contains($libelle, 'uree')) {
            $norme = ($profil === 'NOUVEAU_NE' || $profil === 'FEMME') ? '0.10 - 0.40 g/l' : '0.15 - 0.45 g/l';
            return [['demande' => 'Urée', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'créatinine') || str_contains($libelle, 'creatinine')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '3 - 11 mg/l',
                'ENFANT' => '03 - 06 mg/l',
                'FEMME' => '06 - 11 mg/l',
                default => '07 - 14 mg/l',
            };
            return [['demande' => 'Créatinine', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'calcium')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '80 - 110 mg/l',
                'ENFANT' => '90 - 110 mg/l',
                default => '88 - 104 mg/l',
            };
            return [['demande' => 'Calcium', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'magnésium')) {
            $norme = ($profil === 'NOUVEAU_NE') ? '17 - 25 mg/l' : '18 - 26 mg/l';
            return [['demande' => 'Magnésium', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'glucose')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '0.50 - 1.40 g/l',
                'ENFANT' => '0.60 - 1.00 g/l',
                default => '0.60 - 1.10 g/l',
            };
            return [['demande' => 'Glucose', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'protides totaux')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '45 - 65 g/l',
                'ENFANT' => '67 - 88 g/l',
                'FEMME' => '60 - 84 g/l',
                default => '67 - 93 g/l',
            };
            return [['demande' => 'Protides totaux', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'ferritine')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '25 - 200 ng/ml',
                'ENFANT' => '7 - 140 ng/ml',
                'FEMME' => '20 - 150 ng/ml',
                default => '30 - 300 ng/ml',
            };
            return [['demande' => 'Ferritine', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'fer sérique')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '9 - 30 µmol/l',
                'ENFANT' => '11 - 23 µmol/l',
                'FEMME' => '09 - 28 µmol/l',
                default => '10 - 30 µmol/l',
            };
            return [['demande' => 'Fer sérique', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'gamma gt')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '20 - 180 UI/l',
                'ENFANT' => '< 37 UI/l',
                'FEMME' => '< 35 UI/l',
                default => '< 45 UI/l',
            };
            return [['demande' => 'Gamma GT', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'cpk')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '70 - 380 ng/ml',
                'ENFANT' => '< 75 ng/ml',
                default => '< 90 ng/ml',
            };
            return [['demande' => 'CPK', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'cortisolémie de 8h')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '150 - 700 nmol/l',
                'FEMME' => '125 - 250 nmol/l',
                default => '250 - 550 nmol/l',
            };
            return [['demande' => 'Cortisolémie de 8h', 'norme' => $norme]];
        }

        // =====================================================================
        // 5. BLOCS BILIRUBINE & THYROÏDE
        // =====================================================================

        if (str_contains($libelle, 'bilirubine totale')) {
            $norme = ($profil === 'NOUVEAU_NE') ? '< 60 µmol/l' : '< 21 µmol/l';
            return [['demande' => 'Bilirubine totale', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'bilirubine directe') || str_contains($libelle, 'conjuguée')) {
            $norme = ($profil === 'NOUVEAU_NE') ? '< 20 µmol/l' : '< 4 µmol/l';
            return [['demande' => 'Bilirubine conjuguée directe', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'bilirubine indirecte')) {
            return [['demande' => 'Bilirubine indirecte', 'norme' => '< 17 µmol/l']];
        }

        if (str_contains($libelle, 'tsh')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '1 - 20 mUI/l',
                'FEMME' => '0.7 - 8.4 mUI/l',
                default => '0.4 - 4 mUI/l',
            };
            return [['demande' => 'TSH', 'norme' => $norme]];
        }

        if (str_contains($libelle, 't3')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '1.2 - 2.8 nmol/l',
                'ENFANT' => '1.5 - 4 nmol/l',
                default => '1.3 - 3.10 nmol/l',
            };
            return [['demande' => 'T3', 'norme' => $norme]];
        }

        if (str_contains($libelle, 't4')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '100 - 220 nmol/l',
                'ENFANT' => '70 - 150 nmol/l',
                default => '66 - 181 nmol/l',
            };
            return [['demande' => 'T4 Libre', 'norme' => $norme]];
        }

        // =====================================================================
        // 6. EXAMENS REGROUPÉS (Bilan, Hémostase, Urines) ET CHOLESTEROL
        // =====================================================================

        if (str_contains($libelle, 'transaminases')) {
            return [
                ['demande' => 'ASAT (TGO)', 'norme' => ($profil === 'NOUVEAU_NE') ? '< 70 UI/l' : '< 35 UI/l'],
                ['demande' => 'ALAT (TGP)', 'norme' => '< 40 UI/l'],
            ];
        }

        if (str_contains($libelle, 'ionogramme')) {
            $normeNa = ($profil === 'NOUVEAU_NE') ? '130 - 145 mmol/l' : '135 - 145 mmol/l';
            $normeCl = ($profil === 'NOUVEAU_NE') ? '95 - 110 mmol/l' : '98 - 107 mmol/l';
            $normeHCO3 = ($profil === 'NOUVEAU_NE') ? '16 - 24 mmol/l' : '20 - 28 mmol/l';
            $normeK = match($profil) {
                'NOUVEAU_NE' => '4 - 6 mmol/l',
                'ENFANT' => '3 - 5.5 mmol/l',
                default => '3.5 - 5.0 mmol/l',
            };

            return [
                ['demande' => 'Sodium (Na+)', 'norme' => $normeNa],
                ['demande' => 'Potassium (K+)', 'norme' => $normeK],
                ['demande' => 'Chlore (Cl-)', 'norme' => $normeCl],
                ['demande' => 'Bicarbonates (HCO3-)', 'norme' => $normeHCO3],
            ];
        }

        // Variables pour les normes dynamiques de cholestérol
        $normeCholTotal = match ($profil) {
            'NOUVEAU_NE' => '0.5 - 1.6 g/l',
            'ENFANT' => '< 1.70 g/l',
            default => '1.20 - 2.39 g/l', // HOMME et FEMME
        };

        $normeLDL = match ($profil) {
            'NOUVEAU_NE' => '0.3 - 1 g/l',
            'ENFANT' => '0.6 - 1.29 g/l',
            default => '0.5 - 1.59 g/l', // HOMME et FEMME
        };

        $normeHDL = match ($profil) {
            'NOUVEAU_NE' => '0.2 - 0.6 g/l',
            'ENFANT' => '> 0.45 g/l',
            'FEMME' => '> 0.50 g/l',
            default => '> 0.40 g/l', // HOMME
        };

        if (str_contains($libelle, 'cholestérol total') || $libelle === 'cholesterol total') {
            return [['demande' => 'Cholestérol total', 'norme' => $normeCholTotal]];
        }

        // Si demandé individuellement : LDL
        if (str_contains($libelle, 'ldl')) {
            return [['demande' => 'LDL Cholestérol', 'norme' => $normeLDL]];
        }

        // Si demandé individuellement : HDL
        if (str_contains($libelle, 'hdl')) {
            return [['demande' => 'HDL Cholestérol', 'norme' => $normeHDL]];
        }

        // Si demandé sous forme de Bilan Lipidique
        if (str_contains($libelle, 'lipidique')) {
            return [
                ['demande' => 'Cholestérol total', 'norme' => $normeCholTotal],
                ['demande' => 'HDL Cholestérol', 'norme' => $normeHDL],
                ['demande' => 'LDL Cholestérol', 'norme' => $normeLDL],
                ['demande' => 'Triglycérides', 'norme' => '0.60 - 1.60 g/l'],
            ];
        }

        if (str_contains($libelle, 'bandelette')) {
            return [
                ['demande' => 'Protéine', 'norme' => 'Négatif'],
                ['demande' => 'Glucose', 'norme' => 'Négatif'],
                ['demande' => 'Corps cétoniques', 'norme' => 'Négatif'],
                ['demande' => 'PH', 'norme' => '5 - 6.5'],
                ['demande' => 'Densité urinaire', 'norme' => '1.003'],
            ];
        }

        if (str_contains($libelle, 'nfs') || str_contains($libelle, 'numération')) {
            $normeGB = match($profil) {
                'NOUVEAU_NE' => '9000 - 30000 /mm3',
                'ENFANT' => '6000 - 14000 /mm3',
                default => '4000 - 10000 /mm3',
            };
            $normeGR = $isFemme ? '4 - 5.2 Millions/mm3' : '4.5 - 5.2 Millions/mm3';
            $normeHb = $isFemme ? '12 - 16 g/dl' : '13 - 17 g/dl';
            $normeHte = $isFemme ? '36 - 47 %' : '40 - 52 %';

            return [
                ['demande' => 'Globules Blancs (GB)', 'norme' => $normeGB],
                ['demande' => 'Polynucléaires Neutrophiles', 'norme' => '50 - 70 %'],
                ['demande' => 'Lymphocytes', 'norme' => '20 - 40 %'],
                ['demande' => 'Monocytes', 'norme' => '2 - 10 %'],
                ['demande' => 'Polynucléaires Eosinophiles', 'norme' => '1 - 4 %'],
                ['demande' => 'Polynucléaires Basophiles', 'norme' => '< 1 %'],
                ['demande' => 'Globules Rouges (GR)', 'norme' => $normeGR],
                ['demande' => 'Hémoglobine (Hb)', 'norme' => $normeHb],
                ['demande' => 'Hématocrite', 'norme' => $normeHte],
                ['demande' => 'Plaquettes', 'norme' => '150 000 - 450 000 /mm3'],
            ];
        }

        // =====================================================================
        // 7. EXAMENS STANDARDS (Pas de variation complexe)
        // =====================================================================
        
        if (str_contains($libelle, 'crp')) {
            return [['demande' => 'CRP', 'norme' => '< 6 mg/l']];
        }

        if (str_contains($libelle, 'goutte epaisse')) {
            return [
                ['demande' => 'Goutte épaisse (Recherche de Plasmodium)', 'norme' => 'Négatif / Absence'],
                ['demande' => 'Densité parasitaire', 'norme' => '< 40 parasites/µl'],
            ];
        }

        if (str_contains($libelle, 'hemoglobine glyquee')) {
            $norme = ($profil === 'ENFANT') ? '< 5.7 %' : '< 6 %';
            return [['demande' => 'Hémoglobine glyquée (HbA1c)', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'vs')) {
            $norme = match ($profil) {
                'FEMME' => '0 - 20 mm/h',
                'HOMME' => '0 - 15 mm/h',
                default => '0 - 10 mm/h', // NOUVEAU_NE et ENFANT
            };
            return [['demande' => 'Vitesse de sédimentation (VS)', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'aslo')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '< 25 UI/ml',
                'ENFANT' => '< 333 UI/ml',
                default => '< 200 UI/ml',
            };
            return [['demande' => 'ASLO', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'ige')) {
            $norme = match ($profil) {
                'NOUVEAU_NE' => '< 15 UI/ml',
                'ENFANT' => '10 - 100 UI/ml',
                'FEMME' => '< 150 UI/ml',
                default => '<= 150 UI/ml',
            };
            return [['demande' => 'IgE totale', 'norme' => $norme]];
        }
        
        if (str_contains($libelle, 'troponine')) {
            $norme = ($profil === 'NOUVEAU_NE') ? '< 0.1 ng/ml' : '< 0.2 ng/ml';
            return [['demande' => 'Troponine', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'd dimères') || str_contains($libelle, 'dimeres')) {
            return [['demande' => 'D-Dimère', 'norme' => '< 0.5 mg/l']];
        }

        if (str_contains($libelle, 'psa')) {
            $norme = ($profil === 'NOUVEAU_NE') ? '< 0.1 ng/ml' : '< 4 ng/ml';
            return [['demande' => 'PSA', 'norme' => $norme]];
        }

        if (str_contains($libelle, 'protéine urinaire') || str_contains($libelle, 'proteinurie')) {
            return [['demande' => 'Protéinurie de 24h', 'norme' => '< 0.15 g/24h']];
        }

        if (str_contains($libelle, 'micro albuminurie') || str_contains($libelle, 'micro albuminémie')) {
            return [['demande' => 'Micro-albuminurie', 'norme' => '< 20 mg/l']];
        }

        // Remplacer l'ancien bloc du Taux de Prothrombine (TP) par cette version complète :
        if ((str_contains($libelle, 'tp') || str_contains($libelle, 'prothrombine')) && !str_contains($libelle, 'tgp')) {
            return [
                ['demande' => 'Taux de prothrombine (TP)', 'norme' => '08 - 14 secondes'],
                ['demande' => '% (Pourcentage)', 'norme' => '70 - 100 %'],
                ['demande' => 'INR', 'norme' => '0,70 - 1,30'],
                ['demande' => 'Taux de céphaline activé (TCA)', 'norme' => '25 - 45 secondes'],
            ];
        }

        if (str_contains($libelle, 'tck') || str_contains($libelle, 'tca')) {
            return [['demande' => 'TCK / TCA', 'norme' => '25 - 43 Secondes']];
        }

        if (str_contains($libelle, 'vitamine b12') || str_contains($libelle, 'vit b12')) {
            return [['demande' => 'Vitamine B12', 'norme' => '200 - 900 Pmol/l']];
        }

        if (str_contains($libelle, 'widal') || str_contains($libelle, 'salmonella')) {
            return [
                ['demande' => 'Antigène O (Typhi)', 'norme' => 'Négatif'],
                ['demande' => 'Antigène H (Typhi)', 'norme' => 'Négatif'],
                ['demande' => 'Paratyphi A', 'norme' => 'Négatif'],
                ['demande' => 'Paratyphi B', 'norme' => 'Négatif'],
            ];
        }

        // Mappage de l'ECBU (Examen Cytobactériologique des Urines)
        if (str_contains($libelle, 'ecbu') || str_contains($libelle, 'cytobactériologique des urines') || str_contains($libelle, 'urines')) {
            return [
                // Section Macroscopie
                ['demande' => 'Aspect', 'norme' => 'Limpide', 'groupe' => 'Macroscopie'],
                ['demande' => 'Couleur', 'norme' => 'Jaune citrin', 'groupe' => 'Macroscopie'],
                
                // Section Examen Microscopique
                ['demande' => 'Leucocytes', 'norme' => '< 10 000 /ml (ou < 10/mm³)', 'groupe' => 'Examen microscopique'],
                ['demande' => 'Hématies', 'norme' => '< 10 000 /ml (ou < 10/mm³)', 'groupe' => 'Examen microscopique'],
                ['demande' => 'Cellules épithéliales', 'norme' => 'Rares ou Absentes', 'groupe' => 'Examen microscopique'],
                ['demande' => 'Leucocytes en amas', 'norme' => 'Absence', 'groupe' => 'Examen microscopique'],
                ['demande' => 'Levures', 'norme' => 'Absence', 'groupe' => 'Examen microscopique'],
                ['demande' => 'Parasites', 'norme' => 'Absence', 'groupe' => 'Examen microscopique'],
                ['demande' => 'Microcristaux', 'norme' => 'Absence', 'groupe' => 'Examen microscopique'],
                ['demande' => 'Culot de centrifugation', 'norme' => '', 'groupe' => 'Examen microscopique'],
                
                // Section Quantification Bactérienne
                ['demande' => 'Compte de Kass (Numération des germes)', 'norme' => '< 1 000 UFC/ml', 'groupe' => 'Bactériologie'],
            ];
        }

       // Mappage complet de l'examen des Selles (Selle KOPA & Coproculture)
        if (str_contains($libelle, 'selle kopa') || str_contains($libelle, 'eps') || str_contains($libelle, 'coproculture')) {
            return [
                // Section Macroscopie
                ['demande' => 'Aspect', 'norme' => 'Moulée', 'groupe' => 'Macroscopie'],
                ['demande' => 'Couleur', 'norme' => 'Marron', 'groupe' => 'Macroscopie'],
                ['demande' => 'Présence de sang', 'norme' => 'Absence', 'groupe' => 'Macroscopie'],
                ['demande' => 'Présence de glaires', 'norme' => 'Absence', 'groupe' => 'Macroscopie'],
                
                // Section Examen Direct (Microscopie)
                ['demande' => 'Leucocytes', 'norme' => 'Rares ou Absents', 'groupe' => 'Examen microscopique'],
                ['demande' => 'Hématies', 'norme' => 'Absence', 'groupe' => 'Examen microscopique'],
                ['demande' => 'Levures', 'norme' => 'Absence', 'groupe' => 'Examen microscopique'],
                ['demande' => 'Flore bactérienne', 'norme' => 'Normale / Équilibrée', 'groupe' => 'Examen microscopique'],

                // Section Parasitologie (KOPA)
                ['demande' => 'Kystes et Trophozoïtes (Amibes)', 'norme' => 'Absence', 'groupe' => 'Parasitologie (KOPA)'],
                ['demande' => 'Flagellés (ex: Giardia)', 'norme' => 'Absence', 'groupe' => 'Parasitologie (KOPA)'],
                ['demande' => 'Œufs d\'helminthes', 'norme' => 'Absence', 'groupe' => 'Parasitologie (KOPA)'],
                ['demande' => 'Autres parasites (Anguillules...)', 'norme' => 'Absence', 'groupe' => 'Parasitologie (KOPA)'],

                // Section Bactériologie (Coproculture)
                ['demande' => 'Culture / Isolement sur milieux sélectifs', 'norme' => 'Absence de Salmonella, Shigella, Campylobacter', 'groupe' => 'Bactériologie (Coproculture)'],
                ['demande' => 'Germe(s) pathogène(s) identifié(s)', 'norme' => 'Absence de flore pathogène', 'groupe' => 'Bactériologie (Coproculture)'],
                
                // Section Antibiogramme (Même logique que l'ECBU)
                ['demande' => 'Antibiogramme (si culture positive)', 'norme' => 'À réaliser uniquement en cas d\'isolement d\'un pathogène', 'groupe' => 'Antibiogramme'],
            ];
        }

        // =====================================================================
        // 8. EXAMENS COMPLEXES (Spermogramme, Liquides d'épanchement)
        // =====================================================================

        // A. Examen des liquides d'épanchement ou de ponction
        if (str_contains($libelle, 'liquide') || str_contains($libelle, 'épanchement') || str_contains($libelle, 'epanchement') || str_contains($libelle, 'ponction')) {
            return [
                // Macroscopie
                ['demande' => 'Type de liquide (LCR, Ascite, Pleural...)', 'norme' => '', 'groupe' => 'Macroscopie'],
                ['demande' => 'Aspect du liquide', 'norme' => 'Limpide', 'groupe' => 'Macroscopie'],
                
                // Cytologie (Microscopie)
                ['demande' => 'Leucocytes', 'norme' => '< 5 /mm³', 'groupe' => 'Cytologie (Examen microscopique)'],
                ['demande' => 'Polynucléaires', 'norme' => '%', 'groupe' => 'Cytologie (Examen microscopique)'],
                ['demande' => 'Lymphocytes', 'norme' => '%', 'groupe' => 'Cytologie (Examen microscopique)'],
                ['demande' => 'Hématies', 'norme' => 'Absence', 'groupe' => 'Cytologie (Examen microscopique)'],
                
                // Bactériologie
                ['demande' => 'Coloration de Gram', 'norme' => 'Absence de germes', 'groupe' => 'Bactériologie'],
                ['demande' => 'Culture', 'norme' => 'Négative', 'groupe' => 'Bactériologie'],
            ];
        }

        // B. Spermogramme / Spermocytogramme
        if (str_contains($libelle, 'spermo')) {
            return [
                // Renseignements cliniques
                ['demande' => 'Délai d\'abstinence', 'norme' => '3 à 5 jours', 'groupe' => 'Renseignements cliniques'],
                ['demande' => 'Heure de prélèvement', 'norme' => '', 'groupe' => 'Renseignements cliniques'],
                ['demande' => 'Heure de réception', 'norme' => '', 'groupe' => 'Renseignements cliniques'],
                
                // Macroscopie
                ['demande' => 'Volume', 'norme' => '≥ 1.5 ml', 'groupe' => 'Macroscopie'],
                ['demande' => 'Aspect', 'norme' => 'Opalescent / Grisâtre', 'groupe' => 'Macroscopie'],
                ['demande' => 'Liquéfaction', 'norme' => '< 60 minutes', 'groupe' => 'Macroscopie'],
                ['demande' => 'Viscosité', 'norme' => 'Normale', 'groupe' => 'Macroscopie'],
                ['demande' => 'pH', 'norme' => '≥ 7.2', 'groupe' => 'Macroscopie'],
                
                // Numération et Vitalité
                ['demande' => 'Numération (Concentration)', 'norme' => '20 - 200 millions/ml', 'groupe' => 'Numération & Vitalité'],
                ['demande' => 'Vitalité (1ère heure)', 'norme' => '≥ 58 %', 'groupe' => 'Numération & Vitalité'],
                
                // Mobilité (1ère heure)
                ['demande' => 'Rapide et progressive (RP)', 'norme' => 'RP+LP > 40% ou RP > 32%', 'groupe' => 'Mobilité (1ère heure)'],
                ['demande' => 'Lente et progressive (LP)', 'norme' => '%', 'groupe' => 'Mobilité (1ère heure)'],
                ['demande' => 'Mobile sur place (MP)', 'norme' => '%', 'groupe' => 'Mobilité (1ère heure)'],
                ['demande' => 'Immobile (IM)', 'norme' => '%', 'groupe' => 'Mobilité (1ère heure)'],
                
                // Examen microscopique (Autres cellules)
                ['demande' => 'Agglutination / Agrégation', 'norme' => 'Absence', 'groupe' => 'Éléments cellulaires associés'],
                ['demande' => 'Cellules rondes / Leucocytes', 'norme' => '< 1 million/ml', 'groupe' => 'Éléments cellulaires associés'],
                ['demande' => 'Hématies', 'norme' => 'Absence', 'groupe' => 'Éléments cellulaires associés'],
                ['demande' => 'Cellules épithéliales', 'norme' => 'Rares', 'groupe' => 'Éléments cellulaires associés'],
                ['demande' => 'Cristaux', 'norme' => 'Absence', 'groupe' => 'Éléments cellulaires associés'],
                
                // Morphologie (Spermocytogramme)
                ['demande' => 'Formes normales', 'norme' => '≥ 15 % (Kruger)', 'groupe' => 'Morphologie (Spermocytogramme)'],
                ['demande' => 'Formes anormales (IAM)', 'norme' => '%', 'groupe' => 'Morphologie (Spermocytogramme)'],
                ['demande' => 'Détail : Anomalies de la tête', 'norme' => 'Microcéphale, macrocéphale, allongée...', 'groupe' => 'Morphologie (Spermocytogramme)'],
                ['demande' => 'Détail : Pièce intermédiaire', 'norme' => 'Grêle, angulée, reste cytoplasmique...', 'groupe' => 'Morphologie (Spermocytogramme)'],
                ['demande' => 'Détail : Flagelle', 'norme' => 'Enroulé, court, absent, multiple...', 'groupe' => 'Morphologie (Spermocytogramme)'],
            ];
        }

        // =====================================================================
        // PAR DÉFAUT (Fallback si non trouvé dans les règles ci-dessus)
        // =====================================================================
        return [
            ['demande' => ucfirst($libelleInitial), 'norme' => '']
        ];
    }

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO')"))]
    #[Route('/rapport-agents', name: 'app_laboratoire_rapport_agents', methods: ['GET'])]
    public function rapportAgents(
        Request $request,
        ResultatLaboratoireRepository $resultatRepo,
        UtilisateurRepository $utilisateurRepo
    ): Response {
        return $this->render('laboratoire/rapport_agents.html.twig', $this->buildLaboReportData(
            $request,
            $resultatRepo,
            $utilisateurRepo
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_LABO')"))]
    #[Route('/rapport-agents/pdf', name: 'app_laboratoire_rapport_agents_pdf', methods: ['GET'])]
    public function rapportAgentsPdf(
        Request $request,
        ResultatLaboratoireRepository $resultatRepo,
        UtilisateurRepository $utilisateurRepo
    ): Response {
        $data = $this->buildLaboReportData($request, $resultatRepo, $utilisateurRepo);

        $html = $this->renderView('laboratoire/rapport_agents_pdf.html.twig', array_merge($data, [
            'generatedAt' => new \DateTimeImmutable(),
            'logo_path' => $this->getEmbeddedLogo(),
        ]));

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', $this->getParameter('kernel.project_dir') . '/public');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rapport-laboratoire.pdf"',
        ]);
    }

    private function parseDateFilter(string $value, bool $endOfDay): ?\DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$date instanceof \DateTimeImmutable) {
            return null;
        }

        return $endOfDay ? $date->setTime(23, 59, 59) : $date->setTime(0, 0, 0);
    }

    private function buildLaboReportData(
        Request $request,
        ResultatLaboratoireRepository $resultatRepo,
        UtilisateurRepository $utilisateurRepo
    ): array {
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

        $qb = $resultatRepo->createQueryBuilder('rl')
            ->leftJoin('rl.prescriptionPrestation', 'pp')->addSelect('pp')
            ->leftJoin('pp.consultation', 'c')->addSelect('c')
            ->leftJoin('c.rendezVous', 'r')->addSelect('r')
            ->leftJoin('c.dossierMedical', 'cdm')->addSelect('cdm')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('cdm.patient', 'dmp')->addSelect('dmp')
            ->leftJoin('p.dossierMedical', 'pdm')->addSelect('pdm')
            ->where('rl.dateValidation IS NOT NULL');

        if ($search !== '') {
            $qb->andWhere(
                'LOWER(COALESCE(p.code, dmp.code, \'\')) LIKE :search
                OR LOWER(COALESCE(p.telephone, dmp.telephone, \'\')) LIKE :search
                OR LOWER(COALESCE(pdm.numeroDossier, cdm.numeroDossier, \'\')) LIKE :search
                OR LOWER(CONCAT(COALESCE(p.nom, dmp.nom, \'\'), \' \', COALESCE(p.prenom, dmp.prenom, \'\'))) LIKE :search'
            )
            ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($agentId > 0) {
            $selectedAgent = $utilisateurRepo->find($agentId);
            if ($selectedAgent) {
                // On reconstitue la chaîne exacte sauvegardée en BDD pour filtrer
                $agentLabel = $this->buildLaborantinLabel($selectedAgent);
                $qb->andWhere('rl.validePar = :agentLabel')
                   ->setParameter('agentLabel', $agentLabel);
            }
        }

        if ($dateDebut instanceof \DateTimeImmutable) {
            $qb->andWhere('rl.dateValidation >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin instanceof \DateTimeImmutable) {
            $qb->andWhere('rl.dateValidation <= :dateFin')
                ->setParameter('dateFin', $dateFin);
        }

        /** @var list<ResultatLaboratoire> $resultats */
        $resultats = $qb->orderBy('rl.dateValidation', 'DESC')
            ->getQuery()
            ->getResult();

        $rapportAgents = [];

        foreach ($resultats as $resultat) {
            $agentLabel = $resultat->getValidePar() ?: 'Compte non renseigné';

            if (!isset($rapportAgents[$agentLabel])) {
                $rapportAgents[$agentLabel] = [
                    'libelle' => $agentLabel,
                    'nombreAnalyses' => 0,
                ];
            }
            $rapportAgents[$agentLabel]['nombreAnalyses']++;
        }

        $rapportAgents = array_values($rapportAgents);
        usort(
            $rapportAgents,
            static fn (array $a, array $b) => $b['nombreAnalyses'] <=> $a['nombreAnalyses']
        );

        return [
            'resultats' => $resultats,
            'agents' => $agentFilterLocked && $connectedUser instanceof Utilisateur
                ? [$connectedUser]
                : $utilisateurRepo->findUsersByRoles(['ROLE_LABO']), // Assurez-vous que c'est le bon rôle
            'rapportAgents' => $rapportAgents,
            'agentFilterLocked' => $agentFilterLocked,
            'selectedAgentId' => $agentId,
            'dateDebut' => $dateDebutInput,
            'dateFin' => $dateFinInput,
            'nbAnalyses' => count($resultats),
            'nbAgentsActifs' => count($rapportAgents),
            'search' => $search,
        ];
    }
}