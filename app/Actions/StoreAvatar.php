<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use RuntimeException;

class StoreAvatar
{
    /**
     * Convert an uploaded photo to a square WebP avatar and store it.
     *
     * @return string The relative path on the public disk.
     */
    public function handle(UploadedFile $file): string
    {
        $path = Image::fromUpload($file)
            ->orient()
            ->cover(400, 400)
            ->toWebp()
            ->quality(85)
            ->store('avatars', 'public');

        if ($path === false) {
            throw new RuntimeException('The profile photo could not be stored.');
        }

        return $path;
    }
}
