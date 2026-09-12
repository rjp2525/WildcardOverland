<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * One entry from config('assets.variants'), resolved.
 *
 * A URL names a variant and a width and nothing else, so this is the only
 * place that knows what "card at 640" actually means. Every other part of
 * the system - signing, rendering, the srcset - reads it from here, which is
 * what keeps the URL a browser requests and the image the server produces
 * from drifting apart.
 */
class ImageVariant
{
    /**
     * @param  array{0: int, 1: int}|null  $ratio
     * @param  array<int, int>  $widths
     */
    protected function __construct(
        public readonly string $name,
        public readonly string $fit,
        public readonly ?array $ratio,
        public readonly array $widths,
        public readonly string $sizes,
        public readonly string $format,
    ) {}

    public static function make(string $name): self
    {
        $config = config("assets.variants.{$name}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("Unknown image variant [{$name}].");
        }

        return new self(
            name: $name,
            fit: $config['fit'] ?? 'cover',
            ratio: $config['ratio'] ?? null,
            widths: array_values($config['widths']),
            sizes: $config['sizes'] ?? '100vw',
            format: $config['format'] ?? 'webp',
        );
    }

    /** @return array<int, string> */
    public static function names(): array
    {
        return array_keys((array) config('assets.variants', []));
    }

    /** Every width any variant allows - the outer bound on what can be asked for. */
    public static function everyWidth(): array
    {
        $widths = [];

        foreach ((array) config('assets.variants', []) as $variant) {
            $widths = [...$widths, ...($variant['widths'] ?? [])];
        }

        return array_values(array_unique($widths));
    }

    public function allows(int $width): bool
    {
        return in_array($width, $this->widths, true);
    }

    /** The widest candidate, which is what `src` points at. */
    public function largestWidth(): int
    {
        return max($this->widths);
    }

    /**
     * The height this variant renders at for a given width. Null when the
     * variant has no fixed ratio, since the source's own shape decides.
     */
    public function heightFor(int $width): ?int
    {
        if ($this->ratio === null) {
            return null;
        }

        [$w, $h] = $this->ratio;

        return (int) round($width * $h / $w);
    }

    public function extension(): string
    {
        return $this->format === 'jpeg' ? 'jpg' : $this->format;
    }
}
