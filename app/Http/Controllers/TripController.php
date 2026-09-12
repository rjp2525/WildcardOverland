<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Support\ImagePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    public function index(Request $request): Response
    {
        $trips = Trip::published()
            ->with(['heroImage.file'])
            ->withCount('campsites')
            ->orderByRaw('start_date IS NULL')
            ->orderByDesc('start_date')
            ->paginate(9)
            ->through(fn (Trip $trip) => $this->card($trip));

        return Inertia::render('trips/Index', [
            'trips' => $trips,
        ]);
    }

    public function show(Trip $trip): Response
    {
        abort_unless($trip->isPublished(), 404);

        $trip->load(['heroImage.file', 'images.file', 'campsites']);

        return Inertia::render('trips/Show', [
            'trip' => [
                'name' => $trip->name,
                'headline' => $trip->headline,
                'summary' => $trip->summary,
                'content' => $trip->content,
                'start_date' => $trip->start_date?->toDateString(),
                'end_date' => $trip->end_date?->toDateString(),
                'date_label' => $this->dateLabel($trip),
                'nights' => $trip->calculated_nights,
                'hero' => ImagePresenter::hero($trip->heroImage, $trip->name),
                'gallery' => $trip->images
                    ->map(function ($image) use ($trip) {
                        $presented = ImagePresenter::thumb($image, $image->pivot->caption ?? $trip->name);

                        if ($presented === null) {
                            return null;
                        }

                        // The pivot caption is written for this trip, so it
                        // wins over whatever the image itself is captioned.
                        $presented['caption'] = $image->pivot->caption ?? $presented['caption'];

                        return $presented;
                    })
                    ->filter()
                    ->values(),
                'campsites' => $trip->campsites->map(fn ($campsite) => [
                    'name' => $campsite->name,
                    'nights' => $campsite->nights,
                    'notes' => $campsite->notes,
                    'latitude' => $campsite->latitude === null ? null : (float) $campsite->latitude,
                    'longitude' => $campsite->longitude === null ? null : (float) $campsite->longitude,
                ]),
            ],
            'more' => Trip::published()
                ->whereKeyNot($trip->id)
                ->with('heroImage.file')
                ->withCount('campsites')
                ->orderByDesc('start_date')
                ->take(3)
                ->get()
                ->map(fn (Trip $other) => $this->card($other)),
        ]);
    }

    /**
     * The shape trip cards render, shared by the index, the "more trips"
     * strip and the homepage.
     *
     * @return array<string, mixed>
     */
    public static function cardFor(Trip $trip): array
    {
        return [
            'name' => $trip->name,
            'slug' => $trip->slug,
            'headline' => $trip->headline,
            'url' => route('trips.show', $trip->slug),
            'start_date' => $trip->start_date?->toDateString(),
            'date_label' => static::dateLabelFor($trip),
            'nights' => $trip->calculated_nights,
            'campsites_count' => $trip->campsites_count ?? null,
            'image' => ImagePresenter::card($trip->heroImage, $trip->name),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function card(Trip $trip): array
    {
        return static::cardFor($trip);
    }

    protected function dateLabel(Trip $trip): ?string
    {
        return static::dateLabelFor($trip);
    }

    /**
     * "March 2026", or "March – April 2026" when a trip spans months.
     */
    protected static function dateLabelFor(Trip $trip): ?string
    {
        if ($trip->start_date === null) {
            return null;
        }

        $start = $trip->start_date;
        $end = $trip->end_date;

        if ($end === null || $start->isSameMonth($end)) {
            return $start->format('F Y');
        }

        return $start->isSameYear($end)
            ? $start->format('F').' – '.$end->format('F Y')
            : $start->format('F Y').' – '.$end->format('F Y');
    }
}
