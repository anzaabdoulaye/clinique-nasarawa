<?php

namespace App\Controller;

use App\Entity\Consultation;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Endroid\QrCode\Builder\BuilderInterface;

final class QrCodeController extends AbstractController
{
    #[Route('/qr/consultation/{id}/bon-examens', name: 'app_qr_consultation_bon_examens', methods: ['GET'])]
    public function bonExamensQr(Consultation $consultation): Response
    {
        // ✅ URL ABSOLUE (meilleur pour un QR)
        $payload = $this->generateUrl(
            'app_consultation_examens_bon',
            ['id' => $consultation->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $qrCode = QrCode::create($payload)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelMedium())
            ->setSize(240)
            ->setMargin(10);

        $png = (new PngWriter())->write($qrCode)->getString();

        return new Response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr.png"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}