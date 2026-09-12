<?php

namespace App\Image\Transformations;

use Illuminate\Contracts\Image\Transformation;

/**
 * Lays the site's link card over whatever image it is handed.
 *
 * Registered as a custom transformation rather than done off to one side, so
 * a card is built through the same Image pipeline as every other derivative
 * and picks up the same caching and driver handling.
 */
class OgCard implements Transformation
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $eyebrow = null,
        public readonly bool $hasPhoto = true,
    ) {}
}
