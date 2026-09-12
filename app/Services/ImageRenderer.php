<?php

namespace App\Services;

use App\Support\ImageVariant;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;

/**
 * Produces the actual bytes for a variant, and remembers them.
 *
 * Rendering is expensive enough that it must not happen twice for the same
 * URL, but the results are entirely derived - losing the cache costs time,
 * never data - so it lives on its own disk and can be deleted at any point.
 */
class ImageRenderer
{
    /**
     * @return array{bytes: string, mime: string}
     */
    public function render(string $path, ImageVariant $variant, int $width): array
    {
        $mime = match ($variant->extension()) {
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            default => 'image/webp',
        };

        $key = $this->cacheKey($path, $variant, $width);
        $cache = Storage::disk(config('assets.cache_disk'));

        if ($cache->exists($key)) {
            return ['bytes' => $cache->get($key), 'mime' => $mime];
        }

        $bytes = $this->transform($path, $variant, $width);

        $cache->put($key, $bytes);

        return ['bytes' => $bytes, 'mime' => $mime];
    }

    protected function transform(string $path, ImageVariant $variant, int $width): string
    {
        $image = Image::fromStorage($path, config('assets.disk'))
            // Phones write orientation into EXIF rather than the pixels.
            ->orient();

        $height = $variant->heightFor($width);

        $image = $variant->fit === 'cover' && $height !== null
            ? $image->cover($width, $height)
            // No fixed ratio: fit the whole thing inside the box instead,
            // which is what a logo of unknown shape needs.
            : $image->scale($width, $height);

        return $image
            ->optimize($variant->format, config('assets.quality'))
            ->toBytes();
    }

    /**
     * Keyed on the source's last-modified time as well as its path, so
     * replacing a file cannot leave the old rendering behind.
     */
    protected function cacheKey(string $path, ImageVariant $variant, int $width): string
    {
        $stamp = rescue(
            fn () => Storage::disk(config('assets.disk'))->lastModified($path),
            0,
            report: false,
        );

        $hash = hash('xxh128', implode('|', [$path, $stamp, $variant->name, $width, $variant->format]));

        // Two levels of fan-out: a flat directory of these gets slow to list.
        return sprintf('%s/%s/%s.%s', substr($hash, 0, 2), substr($hash, 2, 2), $hash, $variant->extension());
    }

    /** Drop every rendered derivative. They rebuild on the next request. */
    public function flush(): void
    {
        $cache = Storage::disk(config('assets.cache_disk'));

        foreach ($cache->directories() as $directory) {
            $cache->deleteDirectory($directory);
        }
    }
}
