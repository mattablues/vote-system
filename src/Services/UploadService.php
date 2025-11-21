<?php

declare(strict_types=1);

namespace App\Services;

use Radix\File\Image;
use RuntimeException;

class UploadService
{
    /**
     * Hanterar uppladdning och bearbetning av en bild
     *
     * @param array<string, mixed> $file
     */
    public function uploadImage(array $file, string $uploadDirectory, ?callable $processImageCallback = null, ?string $fileName = null): string
    {
        // Kontrollera om filen har laddats upp korrekt
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Fel vid uppladdning av filen.');
        }

        // Säkerställ att tmp_name är en sträng
        $tmpName = $file['tmp_name'];
        if (!is_string($tmpName) || $tmpName === '') {
            throw new RuntimeException('Ogiltigt tmp_name för uppladdad fil.');
        }

        // Skapa uppladdningskatalog om den inte finns
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0o755, true);
        }

        // Hämta MIME-typ
        $mimeType = mime_content_type($tmpName);
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw new RuntimeException("Bildformat \"$mimeType\" stöds inte."),
        };

        // Skapa filnamn om det inte är specificerat
        $fileName ??= uniqid('image_', true) . '.' . $extension;

        // Fullständig filväg
        $filePath = $uploadDirectory . $fileName;

        // Flytta till uppladdningsmappen
        if (!move_uploaded_file($tmpName, $filePath)) {
            throw new RuntimeException('Misslyckades med att flytta uppladdad fil.');
        }

        // Bearbeta bilden om en callback skickas med
        if ($processImageCallback) {
            $image = new Image($filePath);
            $processImageCallback($image);
            $image->saveImage($filePath);
        }

        // Returnera relativ filväg
        return str_replace(ROOT_PATH . '/public', '', $filePath);
    }

    /**
     * Ladda upp och bearbeta en användaravatar
     *
     * @param array<string, mixed> $file
     */
    public function uploadAvatar(array $file, string $uploadDirectory): string
    {
        return $this->uploadImage(
            $file,
            $uploadDirectory,
            function (Image $image) {
                $image->resizeImage(200, 200, 'crop'); // Beskär bilden för avatar
            },
            'avatar.jpg'
        );
    }

    /**
     * Hanterar uppladdning och bearbetning av en bild
     *
     * @param array<string, mixed> $file
     */
    public function uploadBanner(array $file, string $uploadDirectory): string
    {
        return $this->uploadImage(
            $file,
            $uploadDirectory,
            function (Image $image) {
                $image->resizeImage(1200, 450, 'crop'); // Bannerstorlek
            },
            'banner_' . uniqid() . '.jpg'
        );
    }

    /**
     * Ladda upp och bearbeta en produktbild
     *
     * @param array<string, mixed> $file
     */
    public function uploadProductImage(array $file, string $uploadDirectory): string
    {
        return $this->uploadImage(
            $file,
            $uploadDirectory,
            function (Image $image) {
                $image->resizeImage(600, 600); // Ändra storlek på produktbild
            }
        );
    }
}
