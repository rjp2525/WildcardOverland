<?php

namespace App\Support;

use App\Enums\ImageType;
use App\Models\Image;

/**
 * Turns an Image into the shape the public pages render.
 *
 * Sizes are constrained to ImageEnum's allow-lists, which AssetUrl enforces.
 * Private images resolve to null here as well as being filtered in queries -
 * belt and braces, so a missed `where` can't leak one.
 */
class ImagePresenter
{
    /** Wide banner at the top of a trip or recipe page. */
    public static function hero(?Image $image, ?string $alt = null): ?array
    {
        return static::present($image, 1600, 900, 'crop-center', $alt);
    }

    /** Uniform tile used by trip and recipe cards. */
    public static function card(?Image $image, ?string $alt = null): ?array
    {
        return static::present($image, 600, 300, 'crop-center', $alt);
    }

    /** Square-ish thumbnail for galleries. */
    public static function thumb(?Image $image, ?string $alt = null): ?array
    {
        return static::present($image, 600, 600, 'crop-center', $alt);
    }

    /** Whole image, scaled down to fit - used for logos. */
    public static function contain(?Image $image, int $width = 300, int $height = 146, ?string $alt = null): ?array
    {
        return static::present($image, $width, $height, 'max', $alt, format: 'png');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function present(
        ?Image $image,
        int $width,
        int $height,
        string $fit,
        ?string $alt,
        string $format = 'jpg',
    ): ?array {
        if ($image === null || $image->private || $image->file === null) {
            return null;
        }

        // Logos keep their transparency; photographs are cheaper as jpg.
        if ($image->type === ImageType::Logo || $image->type === ImageType::Graphic) {
            $format = 'png';
        }

        return [
            'url' => AssetUrl::image($image->file, $width, $height, $format, fit: $fit),
            'alt' => $alt ?? $image->caption ?? $image->name ?? '',
            'caption' => $image->caption,
            'width' => $image->width,
            'height' => $image->height,
        ];
    }
}
