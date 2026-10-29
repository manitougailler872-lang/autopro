<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

/**
 * Strict server-side file upload validation. Never trust the browser.
 *
 * - Validates MIME type (from actual file content, not the reported one)
 * - Validates size
 * - Renames files with UUID + safe extension
 * - Prevents directory traversal
 */
class FileUploadService
{
    public const IMAGE_MAX_SIZE = 5 * 1024 * 2048;   // 5 MB
    public const PROOF_MAX_SIZE = 8 * 1024 * 1024;   // 8 MB

    public const IMAGE_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'image/avif' => 'avif',
    ];

    public const PROOF_MIME_TYPES = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf',
    ];

    /**
     * @param array<string,string> $allowedMimes Map mime => extension
     * @return array{filename:string,originalName:string,mimeType:string,size:int}
     * @throws FileException
     */
    public function upload(UploadedFile $file, string $targetDir, array $allowedMimes, int $maxSize): array
    {
        if (!$file->isValid()) {
            throw new FileException('Fichier invalide : '.$file->getErrorMessage());
        }

        $size = $file->getSize();
        if ($size === false || $size <= 0) {
            throw new FileException('Fichier vide.');
        }
        if ($size > $maxSize) {
            throw new FileException(sprintf('Fichier trop volumineux (max %d Mo).', (int) ($maxSize / 1024 / 1024)));
        }

        // Read the actual mime type from file content, not from the browser
        $realMime = $file->getMimeType() ?? '';
        if (!isset($allowedMimes[$realMime])) {
            throw new FileException('Type de fichier non autorisé.');
        }

        $extension = $allowedMimes[$realMime];
        $filename  = Uuid::v7()->toRfc4122().'.'.$extension;

        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                throw new FileException('Impossible de créer le dossier de destination.');
            }
        }

        try {
            $file->move($targetDir, $filename);
        } catch (\Throwable $e) {
            throw new FileException('Échec du déplacement du fichier : '.$e->getMessage());
        }

        return [
            'filename'     => $filename,
            'originalName' => $this->sanitizeOriginalName($file->getClientOriginalName()),
            'mimeType'     => $realMime,
            'size'         => $size,
        ];
    }

    public function remove(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function sanitizeOriginalName(string $name): string
    {
        // Strip any path component and keep only a bounded, printable string
        $name = basename($name);
        $name = preg_replace('/[^\pL\pN \-_.]/u', '', $name) ?? 'file';
        return mb_substr($name, 0, 180);
    }
}
