<?php

namespace App\Http\Controllers;

use App\Image\Transformations\OgCard;
use App\Models\Recipe;
use App\Models\Trip;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The image a link turns into when somebody shares it.
 *
 * Unsigned and on a readable URL, because the whole point is that a crawler
 * can fetch it without knowing anything, and because the platforms cache
 * these hard: a URL that changes is a card that never updates.
 */
class OgCardController extends Controller
{
    /** Pages with no record behind them still want a card of their own. */
    protected const PAGES = [
        'home' => ['Whichever road looks more interesting', 'Wildcard Overland'],
        'about' => ['A Tacoma, a camper and a German Shepherd', 'About'],
        'rig' => ['Every part on the truck, and where it sits', 'The rig'],
        'trips' => ['Where the truck has been', 'Trips'],
        'recipes' => ['Food worth making a long way from a kitchen', 'Camp recipes'],
    ];

    public function __invoke(string $kind, string $slug): Response
    {
        [$title, $eyebrow, $source] = $this->subject($kind, $slug);

        $key = 'og/'.$kind.'/'.$slug.'.jpg';
        $cache = Storage::disk(config('assets.cache_disk'));

        if (! $cache->exists($key)) {
            try {
                $cache->put($key, $this->render($title, $eyebrow, $source));
            } catch (Throwable $e) {
                Log::warning('Link card could not be drawn', [
                    'kind' => $kind,
                    'slug' => $slug,
                    'error' => $e->getMessage(),
                ]);

                // No card at all beats a broken one: the platforms just fall
                // back to a text-only preview.
                abort(404);
            }
        }

        return response($cache->get($key), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    protected function render(string $title, ?string $eyebrow, ?string $source): string
    {
        $image = $source !== null
            ? Image::fromStorage($source, config('assets.disk'))
            : Image::fromPath(resource_path('img/night-camp-card.jpg'));

        return $image
            ->transform(new OgCard($title, $eyebrow, hasPhoto: true))
            ->optimize('jpg', 84)
            ->toBytes();
    }

    /**
     * @return array{0: string, 1: string|null, 2: string|null}
     */
    protected function subject(string $kind, string $slug): array
    {
        if ($kind === 'trips') {
            $trip = Trip::published()->with('heroImage.file')->where('slug', $slug)->firstOrFail();

            return [$trip->name, 'Trip', $trip->heroImage?->file?->stored_path];
        }

        if ($kind === 'recipes') {
            $recipe = Recipe::published()->with('heroImage.file')->where('slug', $slug)->firstOrFail();

            return [$recipe->name, $recipe->meal_type->label(), $recipe->heroImage?->file?->stored_path];
        }

        abort_unless($kind === 'page' && isset(static::PAGES[$slug]), 404);

        return [static::PAGES[$slug][0], static::PAGES[$slug][1], null];
    }

    /** Drops every drawn card so a design change actually shows up. */
    public static function flush(): void
    {
        Storage::disk(config('assets.cache_disk'))->deleteDirectory('og');
    }
}
