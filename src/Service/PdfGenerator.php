<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

class PdfGenerator
{
    public function __construct(
        private readonly Environment $twig,

        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * Génère le contenu binaire du PDF.
     */
    public function generate(
        string $template,
        array $data = [],
        string $paper = 'A4',
        string $orientation = 'portrait'
    ): string {
        $html = $this->twig->render($template, $data);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Génère le PDF et le stocke dans public/documents.
     *
     * @return string Chemin absolu du fichier créé
     */
    public function save(
        string $template,
        array $data,
        string $filename
    ): string {
        $directory = $this->projectDir . '/public/documents';

        // Création automatique du dossier
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException(
                    sprintf(
                        'Impossible de créer le dossier "%s".',
                        $directory
                    )
                );
            }
        }

        // Sécurisation du nom
        $filename = $this->sanitizeFilename($filename);

        if (!str_ends_with(
            strtolower($filename),
            '.pdf'
        )) {
            $filename .= '.pdf';
        }

        $path = $directory . '/' . $filename;

        $pdf = $this->generate(
            $template,
            $data
        );

        if (file_put_contents($path, $pdf) === false) {
            throw new \RuntimeException(
                sprintf(
                    'Impossible d\'enregistrer le PDF "%s".',
                    $path
                )
            );
        }

        return $path;
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace(
            '/[^a-zA-Z0-9._-]/',
            '-',
            $filename
        );

        return trim($filename, '-');
    }
}