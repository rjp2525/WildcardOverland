<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\Trip;
use Illuminate\Http\Response;

/**
 * The sitemap, built from what is actually published.
 *
 * Generated rather than kept as a file so it cannot go stale: a trip that
 * goes live is in here on the next request, and one that goes back to draft
 * is gone from it.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => route('homepage'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => route('trips.index'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('recipes.index'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('rig'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => route('about'), 'priority' => '0.5', 'changefreq' => 'yearly'],
        ];

        foreach (Trip::published()->get(['slug', 'updated_at']) as $trip) {
            $urls[] = [
                'loc' => route('trips.show', $trip->slug),
                'lastmod' => $trip->updated_at?->toDateString(),
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        foreach (Recipe::published()->get(['slug', 'updated_at']) as $recipe) {
            $urls[] = [
                'loc' => route('recipes.show', $recipe->slug),
                'lastmod' => $recipe->updated_at?->toDateString(),
                'priority' => '0.7',
                'changefreq' => 'monthly',
            ];
        }

        $body = view('sitemap', ['urls' => $urls])->render();

        return response($body, 200, ['Content-Type' => 'application/xml']);
    }
}
