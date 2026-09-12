<?php

namespace App\Support;

use App\Enums\ImageType;
use App\Models\Image;

/**
 * Turns an Image into the shape ResponsiveImage.vue renders.
 *
 * Every image goes out as a full set of candidates rather than one fixed
 * rendering, so the browser downloads the size it is actually going to
 * display - a phone should never be made to fetch a 1600px hero.
 *
 * Private images resolve to null here as well as being filtered in queries:
 * belt and braces, so a missed `where` cannot leak one.
 */
class ImagePresenter
{
    /** Wide banner at the top of a trip or recipe page. */
    public static function hero(?Image $image, ?string $alt = null): ?array
    {
        return static::present($image, 'hero', $alt);
    }

    /** Uniform tile used by trip and recipe cards. */
    public static function card(?Image $image, ?string $alt = null): ?array
    {
        return static::present($image, 'card', $alt);
    }

    /** Square thumbnail for galleries. */
    public static function thumb(?Image $image, ?string $alt = null): ?array
    {
        return static::present($image, 'thumb', $alt);
    }

    /** The card a link turns into when someone shares it. */
    public static function og(?Image $image, ?string $alt = null): ?array
    {
        return static::present($image, 'og', $alt);
    }

    /** Whole image, scaled down to fit - used for logos. */
    public static function contain(?Image $image, ?string $alt = null): ?array
    {
        return static::present($image, 'logo', $alt);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function present(?Image $image, string $variantName, ?string $alt): ?array
    {
        if ($image === null || $image->private || $image->file === null) {
            return null;
        }

        // Logos and diagrams need their transparency, so they keep the
        // variant's own format rather than being flattened into webp.
        $variantName = $variantName !== 'og'
            && ($image->type === ImageType::Logo || $image->type === ImageType::Graphic)
            ? 'logo'
            : $variantName;

        $variant = ImageVariant::make($variantName);
        $width = $variant->largestWidth();

        return [
            'src' => AssetUrl::image($image->file, $variant->name, $width),
            'srcset' => AssetUrl::srcset($image->file, $variant->name),
            'sizes' => $variant->sizes,
            // The rendered size, not the source's: it is what stops the page
            // reflowing once the image arrives.
            'width' => $width,
            'height' => $variant->heightFor($width) ?? static::scaledHeight($image, $width),
            'alt' => $alt ?? $image->caption ?? $image->name ?? '',
            'caption' => $image->caption,
            'color' => $image->dominant_color,
        ];
    }

    /**
     * How tall a ratio-less variant ends up, from the source's own shape.
     * Null when the source was never measured, in which case the markup
     * simply goes without and the browser works it out on arrival.
     */
    protected static function scaledHeight(Image $image, int $width): ?int
    {
        if (! $image->width || ! $image->height) {
            return null;
        }

        // `scale` never enlarges, so a small source keeps its own size.
        $width = min($width, $image->width);

        return (int) round($width * $image->height / $image->width);
    }
}
