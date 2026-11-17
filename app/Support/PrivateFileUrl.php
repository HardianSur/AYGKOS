<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

/**
 * Membentuk tautan sementara untuk berkas pada disk privat (`local`).
 *
 * Adapter lokal tidak mendukung Storage::temporaryUrl(), sehingga tanda
 * tangan dibuat lewat route `storage.local` yang sudah memvalidasi tanda
 * tangan relatif dan mencegah path traversal.
 */
final class PrivateFileUrl
{
    /** Masa berlaku tautan dalam menit. */
    public const EXPIRY_MINUTES = 10;

    /**
     * Bentuk URL bertanda tangan untuk path berkas privat.
     */
    public static function for(string $path): string
    {
        // Route `storage.local` memakai hasValidRelativeSignature(), sehingga
        // tanda tangan harus dibentuk dari URL relatif, bukan URL absolut.
        return URL::temporarySignedRoute(
            'storage.local',
            now()->addMinutes(self::EXPIRY_MINUTES),
            ['path' => ltrim($path, '/')],
            absolute: false,
        );
    }
}
