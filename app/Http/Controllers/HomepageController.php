<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\Trip;
use App\Support\Seo;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomepageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $latestTrip = Trip::published()
            ->orderByRaw('start_date IS NULL')
            ->orderByDesc('start_date')
            ->first();

        return Inertia::render('Homepage', [
            'seo' => Seo::make(
                title: 'Wildcard Overland',
                description: Seo::DEFAULT_DESCRIPTION,
                card: route('og.card', ['kind' => 'page', 'slug' => 'home']),
            ),
            // Drives the hero's primary call to action, which previously
            // pointed at an empty anchor.
            'latestTrip' => $latestTrip === null ? null : [
                'name' => $latestTrip->name,
                'url' => route('trips.show', $latestTrip->slug),
            ],
            'hasRecipes' => Recipe::published()->exists(),

            'trips' => Trip::published()
                ->with('heroImage.file')
                ->withCount('campsites')
                ->orderByRaw('start_date IS NULL')
                ->orderByDesc('start_date')
                ->take(3)
                ->get()
                ->map(fn (Trip $trip) => TripController::cardFor($trip))
                ->all(),

            'recipes' => Recipe::published()
                ->withRatingSummary()
                ->with('heroImage.file')
                ->orderByDesc('published_at')
                ->take(3)
                ->get()
                ->map(fn (Recipe $recipe) => RecipeController::cardFor($recipe))
                ->all(),

            'gallery' => SiteContent::gallery(),
            'modifications' => SiteContent::timeline(limit: 4),
            'campsites' => $campsites = SiteContent::campsitePoints(),
            'mapFocus' => SiteContent::mapFocus($campsites),
            'stats' => SiteContent::stats(),
            'partners' => SiteContent::partners(),
        ]);
    }
}
