<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Resizes all uploaded vehicle images to a fixed canvas (cover-crop),
 * so every image in the catalog has the exact same aspect and size.
 *
 * Uses PHP GD (bundled with php-cli in most distros / our Dockerfile
 * already installs libpng/gd). No external dependency.
 */
class ImageResizerService
{
    public const TARGET_WIDTH  = 1200;
    public const TARGET_HEIGHT = 800; // 3:2 aspect — matches vehicle card
    public const JPEG_QUALITY  = 85;

    /**
     * Resizes and cover-crops the image AT $path in place.
     * Preserves original format (jpg/png/webp).
     */
    public function resizeInPlace(string $path): void
    {
        if (!is_file($path) || !extension_loaded('gd')) {
            return;
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return;
        }
        [$srcW, $srcH] = $info;
        $mime = $info['mime'] ?? '';

        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => false,
        };
        if ($src === false) {
            return;
        }

        // Cover-crop: compute crop rectangle so target aspect is fully filled
        $targetRatio = self::TARGET_WIDTH / self::TARGET_HEIGHT;
        $srcRatio    = $srcW / $srcH;

        if ($srcRatio > $targetRatio) {
            // Source is wider than target → crop left/right
            $cropH = $srcH;
            $cropW = (int) round($srcH * $targetRatio);
            $cropX = (int) round(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            // Source is taller → crop top/bottom
            $cropW = $srcW;
            $cropH = (int) round($srcW / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($srcH - $cropH) / 2);
        }

        $dst = imagecreatetruecolor(self::TARGET_WIDTH, self::TARGET_HEIGHT);

        // Preserve alpha for PNG/WebP
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, self::TARGET_WIDTH, self::TARGET_HEIGHT, $transparent);
        }

        imagecopyresampled(
            $dst, $src,
            0, 0, $cropX, $cropY,
            self::TARGET_WIDTH, self::TARGET_HEIGHT,
            $cropW, $cropH
        );

        match ($mime) {
            'image/jpeg' => imagejpeg($dst, $path, self::JPEG_QUALITY),
            'image/png'  => imagepng($dst,  $path, 6),
            'image/webp' => imagewebp($dst, $path, self::JPEG_QUALITY),
        };

        imagedestroy($src);
        imagedestroy($dst);
    }
}
