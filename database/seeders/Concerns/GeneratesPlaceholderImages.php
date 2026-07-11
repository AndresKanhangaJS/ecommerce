<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait GeneratesPlaceholderImages
{
    // Generates a solid-color JPEG with the label printed on it and stores it on the
    // "public" disk, so seeded rows have something real behind url('storage', $path)
    // instead of a path to a file that doesn't exist.
    private function placeholderImage(string $folder, string $label, array $rgb): string
    {
        $path = "{$folder}/".Str::slug($label).'.jpg';

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        [$r, $g, $b] = $rgb;

        $image = imagecreatetruecolor(600, 400);
        imagefill($image, 0, 0, imagecolorallocate($image, $r, $g, $b));
        imagestring($image, 5, 20, 190, Str::ascii($label), imagecolorallocate($image, 255, 255, 255));

        ob_start();
        imagejpeg($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($path, $contents);

        return $path;
    }
}
