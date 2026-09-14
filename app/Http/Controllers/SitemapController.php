<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\Trip;
use App\Support\ImagePresenter;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * The sitemap, built from what is actually published.
 *
 * Generated rather than kept as a file so it cannot go stale: a trip that
 * goes live is in here on the next request, and one that goes back to draft
 * is gone from it.
 *
 * Entries carry their photograph as well as their URL. Image discovery is
 * otherwise up to a crawler noticing an img tag on a page it has already
 * decided to render, and a recipe's hero is the thing most likely to bring
 * anyone here from an image search.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ...$this->staticPages(),
            ...$this->trips(),
            ...$this->recipes(),
        ];

        return response(view('sitemap', ['urls' => $urls])->render(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * The pages that always exist.
     *
     * Their lastmod is the newest thing they list, because that is what
     * actually changes about them. A fixed date would be a lie and no date
     * makes a crawler guess.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function staticPages(): array
    {
        $newestTrip = Trip::published()->max('updated_at');
        $newestRecipe = Recipe::published()->max('updated_at');
        $newest = collect([$newestTrip, $newestRecipe])->filter()->max();

        return [
            [
                'loc' => route('homepage'),
                'lastmod' => $this->date($newest),
                'priority' => '1.0',
                'changefreq' => 'weekly',
            ],
            [
                'loc' => route('trips.index'),
                'lastmod' => $this->date($newestTrip),
                'priority' => '0.9',
                'changefreq' => 'weekly',
            ],
            [
                'loc' => route('recipes.index'),
                'lastmod' => $this->date($newestRecipe),
                'priority' => '0.9',
                'changefreq' => 'weekly',
            ],
            ['loc' => route('rig'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => route('about'), 'priority' => '0.5', 'changefreq' => 'yearly'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function trips(): array
    {
        return Trip::published()
            ->with('heroImage.file')
            ->get()
            ->map(fn (Trip $trip) => array_filter([
                'loc' => route('trips.show', $trip->slug),
                'lastmod' => $this->date($trip->updated_at),
                'priority' => '0.8',
                'changefreq' => 'monthly',
                'image' => $this->image($trip->heroImage, $trip->name),
            ], fn ($value) => $value !== null))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function recipes(): array
    {
        return Recipe::published()
            ->with('heroImage.file')
            ->get()
            ->map(fn (Recipe $recipe) => array_filter([
                'loc' => route('recipes.show', $recipe->slug),
                'lastmod' => $this->date($recipe->updated_at),
                'priority' => '0.7',
                'changefreq' => 'monthly',
                'image' => $this->image($recipe->heroImage, $recipe->name),
            ], fn ($value) => $value !== null))
            ->all();
    }

    /**
     * @return array<string, string>|null
     */
    protected function image(mixed $image, string $title): ?array
    {
        // Goes through the presenter, so a private image is left out here
        // exactly as it is left off the page.
        $presented = ImagePresenter::hero($image, $title);

        return $presented === null ? null : [
            'loc' => $presented['src'],
            'title' => $title,
        ];
    }

    protected function date(mixed $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toDateString();
    }
}
