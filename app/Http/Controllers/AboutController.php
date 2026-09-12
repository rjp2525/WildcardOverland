<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\VehicleModification;
use App\Support\ImagePresenter;
use App\Support\Seo;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('About', [
            'seo' => Seo::make(
                title: 'About',
                description: 'Who is driving, what the truck is, and why any of this ended up on the internet. Reno, a 2021 Tacoma and a German Shepherd called Maya.',
                card: route('og.card', ['kind' => 'page', 'slug' => 'about']),
            ),
            'stats' => SiteContent::stats(),
            'rig' => $this->rig(),
            'latestTrip' => $this->latestTrip(),
            'partners' => SiteContent::partners(),
            'timeline' => SiteContent::timeline(),
        ]);
    }

    /**
     * Counts for the intro panel, taken from the parts list so the page
     * cannot claim something the rig page contradicts.
     *
     * @return array{parts: int, years: int}
     */
    protected function rig(): array
    {
        $first = VehicleModification::min('install_date');

        return [
            'parts' => VehicleModification::count(),
            'years' => $first === null
                ? 0
                : (int) floor((time() - strtotime((string) $first)) / (365.25 * 86400)),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function latestTrip(): ?array
    {
        $trip = Trip::published()
            ->with('heroImage.file')
            ->orderByRaw('start_date IS NULL')
            ->orderByDesc('start_date')
            ->first();

        return $trip === null ? null : [
            'name' => $trip->name,
            'headline' => $trip->headline,
            'url' => route('trips.show', $trip->slug),
            'image' => ImagePresenter::card($trip->heroImage, $trip->name),
        ];
    }
}
