<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Image\Image as ImagePipeline;
use Illuminate\Support\Facades\Image;
use RuntimeException;

class StoreImage
{
    /**
     * Convert an uploaded photo to a square WebP avatar and store it.
     *
     * @return string The relative path on the public disk.
     */
    public function square(UploadedFile $file): string
    {
        $image = Image::fromUpload($file)
            ->orient()
            ->cover(400, 400);

        return $this->storeWebp($image, 'avatars', 85);
    }

    /**
     * Convert an uploaded photo to a scaled WebP image and store it.
     *
     * The longest side is capped at 1600 px without upscaling smaller
     * images or cropping their aspect ratio.
     *
     * @return string The relative path on the public disk.
     */
    public function scaled(UploadedFile $file): string
    {
        $image = Image::fromUpload($file)
            ->orient()
            ->scale(1600, 1600);

        return $this->storeWebp($image, 'kosts', 80);
    }

    /**
     * Store a prepared image as WebP on the public disk.
     *
     * @param  int<1, 100>  $quality
     */
    private function storeWebp(ImagePipeline $image, string $directory, int $quality): string
    {
        $path = $image
            ->toWebp()
            ->quality($quality)
            ->store($directory, 'public');

        if ($path === false) {
            throw new RuntimeException('The image could not be stored.');
        }

        return $path;
    }
}
