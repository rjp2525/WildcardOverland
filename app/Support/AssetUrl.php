<?php

namespace App\Support;

use App\Enums\ImageEnum;
use App\Models\File;
use InvalidArgumentException;
use League\Glide\Signatures\SignatureFactory;

/**
 * Builds signed URLs for the Glide asset route.
 *
 * AssetController validates the signature against the *whole* request, and
 * AssetFileRequest::prepareForValidation() merges `path`, `q` and `fit` in
 * before that happens. So the signed payload has to include `path` even
 * though it travels in the route rather than the query string, and `q`/`fit`
 * are always sent explicitly rather than relying on their defaults.
 */
class AssetUrl
{
    /**
     * A resized image URL for a stored file.
     *
     * @param  int  $width  One of ImageEnum::ALLOWED_WIDTHS
     * @param  int  $height  One of ImageEnum::ALLOWED_HEIGHTS
     */
    public static function image(
        File $file,
        int $width,
        int $height,
        string $format = 'png',
        int $quality = ImageEnum::DEFAULT_QUALITY,
        string $fit = ImageEnum::DEFAULT_FITMENT,
    ): string {
        static::guard($width, $height, $format, $quality, $fit);

        return static::build($file->stored_path, [
            't' => 'i',
            'w' => $width,
            'h' => $height,
            'fm' => $format,
            'q' => $quality,
            'fit' => $fit,
        ]);
    }

    /**
     * @param  array<string, scalar>  $params
     */
    protected static function build(string $path, array $params): string
    {
        $path = ltrim($path, '/');

        // `path` is signed but not emitted: the route supplies it, and the
        // form request merges it back in before validation.
        $signature = SignatureFactory::create(config('app.key'))
            ->generateSignature("/assets/{$path}", [...$params, 'path' => $path]);

        return url("/assets/{$path}").'?'.http_build_query([...$params, 's' => $signature]);
    }

    protected static function guard(int $width, int $height, string $format, int $quality, string $fit): void
    {
        // AssetFileRequest rejects anything outside these sets, so failing
        // here surfaces the mistake at the call site instead of as a 422.
        $checks = [
            'width' => [$width, ImageEnum::ALLOWED_WIDTHS],
            'height' => [$height, ImageEnum::ALLOWED_HEIGHTS],
            'format' => [$format, ImageEnum::ALLOWED_TYPES],
            'quality' => [$quality, ImageEnum::ALLOWED_QUALITIES],
            'fit' => [$fit, ImageEnum::ALLOWED_FITMENTS],
        ];

        foreach ($checks as $name => [$value, $allowed]) {
            if (! in_array($value, $allowed, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Unsupported asset %s [%s]. Allowed: %s.',
                    $name,
                    $value,
                    implode(', ', $allowed),
                ));
            }
        }
    }
}
