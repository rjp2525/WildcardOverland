<?php

namespace App\Support;

use App\Models\File;

/**
 * Builds signed URLs for the asset route.
 *
 * A URL carries a variant name and a width, both signed, so the set of
 * images the server can be asked to produce is exactly the table in
 * config('assets.variants') - there is no size parameter to tamper with.
 *
 * AssetController validates the signature against the whole request and
 * AssetFileRequest merges `path` in before that happens, so `path` has to be
 * part of the signed payload even though it travels in the route rather than
 * the query string.
 */
class AssetUrl
{
    /** The URL for one candidate: a variant rendered at one width. */
    public static function image(File $file, string $variant, ?int $width = null): string
    {
        $variant = ImageVariant::make($variant);
        $width ??= $variant->largestWidth();

        return static::build($file->stored_path, $variant->name, $width);
    }

    /**
     * Every candidate for a variant, as a srcset value.
     *
     * The browser picks from these knowing the viewport and pixel density,
     * which is information the server does not have.
     */
    public static function srcset(File $file, string $variant): string
    {
        // One file serves every width, so a list of candidates would offer
        // the browser the same bytes several times over.
        if ($file->isVector()) {
            return '';
        }

        $variant = ImageVariant::make($variant);

        return implode(', ', array_map(
            fn (int $width) => static::build($file->stored_path, $variant->name, $width)." {$width}w",
            $variant->widths,
        ));
    }

    protected static function build(string $path, string $variant, int $width): string
    {
        $path = ltrim($path, '/');

        $params = ['v' => $variant, 'w' => $width];

        $signature = AssetSignature::generate("/assets/{$path}", [...$params, 'path' => $path]);

        return url("/assets/{$path}").'?'.http_build_query([...$params, 's' => $signature]);
    }
}
