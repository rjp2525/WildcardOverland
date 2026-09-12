<?php

namespace App\Services;

use App\Image\Transformations\OgCard;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

/**
 * Draws the link card.
 *
 * A crawler's preview is often the only part of the site somebody sees
 * before deciding whether to click, so it gets the brand face and the page's
 * own title rather than a bare photograph.
 */
class OgCardRenderer
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    protected const BRAND = '#e85a2f';

    public function apply(ImageInterface $image, OgCard $card): ImageInterface
    {
        $image = $image->cover(static::WIDTH, static::HEIGHT);

        $this->scrim($image, $card->hasPhoto);

        // A brand rule, then the eyebrow, then the title, bottom left.
        $image->drawRectangle(function ($rect) {
            $rect->at(72, 380);
            $rect->size(84, 6);
            $rect->background(static::BRAND);
        });

        if ($card->eyebrow) {
            $image->text(mb_strtoupper($card->eyebrow), 72, 428, function (FontFactory $font) {
                $font->filename($this->font('Bold'));
                $font->size(26);
                $font->color(static::BRAND);
                $font->align('left', 'top');
            });
        }

        $image->text($this->fit($card->title), 72, 470, function (FontFactory $font) {
            $font->filename($this->font('Extrabold'));
            $font->size(62);
            $font->color('#ffffff');
            $font->align('left', 'top');
            $font->lineHeight(1.12);
            $font->wrap(1010);
        });

        $image->text('WILDCARDOVERLAND.COM', 72, 566, function (FontFactory $font) {
            $font->filename($this->font('Bold'));
            $font->size(22);
            $font->color('rgba(255, 255, 255, 0.55)');
            $font->align('left', 'top');
        });

        return $image;
    }

    /**
     * Darkens the lower half so white type stays readable over a photograph
     * that could be any brightness at all.
     *
     * Built from thin bands rather than a gradient, because there is no
     * gradient fill to reach for. They must not overlap: a shared row gets
     * painted twice and the seam shows up as a stripe across the picture.
     */
    protected function scrim(ImageInterface $image, bool $hasPhoto): void
    {
        if (! $hasPhoto) {
            $image->drawRectangle(function ($rect) {
                $rect->at(0, 0);
                $rect->size(static::WIDTH, static::HEIGHT);
                $rect->background('rgba(10, 10, 12, 0.82)');
            });

            return;
        }

        $band = 3;
        $from = 150;
        $steps = (int) ceil((static::HEIGHT - $from) / $band);

        for ($i = 0; $i < $steps; $i++) {
            $top = $from + $i * $band;
            // Eased rather than linear, so the top of the ramp stays invisible.
            $alpha = round(0.9 * (($i / $steps) ** 1.6), 3);

            $image->drawRectangle(function ($rect) use ($top, $band, $alpha) {
                $rect->at(0, $top);
                $rect->size(static::WIDTH, $band);
                $rect->background('rgba(10, 10, 12, '.$alpha.')');
            });
        }
    }

    /**
     * Long titles get smaller rather than spilling off the card. Three lines
     * at this size is the most that fits above the domain.
     */
    protected function fit(string $title): string
    {
        return mb_strlen($title) > 84 ? mb_substr($title, 0, 81).'...' : $title;
    }

    protected function font(string $weight): string
    {
        return resource_path("fonts/GearedSlab-{$weight}.ttf");
    }
}
