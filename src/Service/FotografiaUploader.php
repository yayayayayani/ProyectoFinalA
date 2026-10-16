<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FotografiaUploader
{
    public function __construct(private readonly string $directory)
    {
    }

    /** Recibe únicamente archivos que ya superaron la validación del formulario. */
    public function upload(UploadedFile $file): string
    {
        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new FileException('Formato de fotografía no permitido.'),
        };

        // Nunca usamos el nombre ni la ruta enviados por el navegador.
        $filename = bin2hex(random_bytes(16)).'.'.$extension;
        $file->move($this->directory, $filename);

        return $filename;
    }

    public function path(?string $filename): ?string
    {
        // También protege la lectura frente a rutas antiguas o manipuladas.
        if ($filename === null || !preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $filename)) {
            return null;
        }

        $path = $this->directory.DIRECTORY_SEPARATOR.$filename;

        return is_file($path) && is_readable($path) ? $path : null;
    }
}
