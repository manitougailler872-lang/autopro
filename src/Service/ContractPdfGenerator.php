<?php

declare(strict_types=1);

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Rend templates/contract/contrat_vente.html.twig en PDF.
 * Sans $evidence : contrat à signer. Avec $evidence : contrat signé + page de preuve.
 */
final class ContractPdfGenerator
{
    public function __construct(private readonly Environment $twig)
    {
    }

    /**
     * @param array<string, mixed>      $context  contract, reservation, vehicle, customer, seller (tableaux de scalaires)
     * @param array<string, mixed>|null $evidence signerName, signerEmail, signerPhone, image (data URL PNG), signedAt,
     *                                            ip, userAgent, otpVerifiedAt, documentHash, transactionId
     */
    public function render(array $context, ?array $evidence = null): string
    {
        $html = $this->twig->render('contract/contrat_vente.html.twig', $context + ['signature' => $evidence]);
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}
