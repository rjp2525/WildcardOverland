<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TripRequest;
use App\Models\Trip;
use App\Support\AdminTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    public function index(Request $request): Response
    {
        $table = AdminTable::for(Trip::query()->withCount('campsites'), $request)
            ->searchable(['name', 'headline', 'slug'])
            ->sortable(['name', 'start_date', 'end_date', 'published_at', 'created_at'], 'start_date');

        return Inertia::render('admin/trips/Index', [
            'trips' => $table->paginate()->through(fn (Trip $trip) => [
                'id' => $trip->id,
                'name' => $trip->name,
                'slug' => $trip->slug,
                'headline' => $trip->headline,
                'start_date' => $trip->start_date?->toDateString(),
                'end_date' => $trip->end_date?->toDateString(),
                'nights' => $trip->calculated_nights,
                'campsites_count' => $trip->campsites_count,
                'is_draft' => $trip->is_draft,
                'published_at' => $trip->published_at?->toDateTimeString(),
            ]),
            'filters' => $table->state(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/trips/Edit', [
            'trip' => null,
        ]);
    }

    public function store(TripRequest $request): RedirectResponse
    {
        $trip = DB::transaction(function () use ($request): Trip {
            $trip = Trip::create($request->safe()->except('campsites'));

            $this->syncCampsites($trip, $request->validated('campsites', []));

            return $trip;
        });

        return redirect()
            ->route('admin.trips.edit', $trip)
            ->with('success', "Trip \"{$trip->name}\" created.");
    }

    public function edit(Trip $trip): Response
    {
        return Inertia::render('admin/trips/Edit', [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'slug' => $trip->slug,
                'headline' => $trip->headline,
                'summary' => $trip->summary,
                'content' => $trip->content,
                'start_date' => $trip->start_date?->toDateString(),
                'end_date' => $trip->end_date?->toDateString(),
                'is_draft' => $trip->is_draft,
                'published_at' => $trip->published_at?->format('Y-m-d\TH:i'),
                'nights' => $trip->calculated_nights,
                'campsites' => $trip->campsites->map(fn ($campsite) => [
                    'id' => $campsite->id,
                    'name' => $campsite->name,
                    'latitude' => $campsite->latitude,
                    'longitude' => $campsite->longitude,
                    'nights' => $campsite->nights,
                    'notes' => $campsite->notes,
                ]),
            ],
        ]);
    }

    public function update(TripRequest $request, Trip $trip): RedirectResponse
    {
        DB::transaction(function () use ($request, $trip): void {
            $trip->update($request->safe()->except('campsites'));

            $this->syncCampsites($trip, $request->validated('campsites', []));
        });

        return back()->with('success', 'Trip updated.');
    }

    public function destroy(Trip $trip): RedirectResponse
    {
        $trip->delete();

        return redirect()
            ->route('admin.trips.index')
            ->with('success', "Trip \"{$trip->name}\" deleted.");
    }

    /**
     * Replace the trip's campsites with the submitted set, preserving ids so
     * existing rows are updated rather than recreated.
     *
     * @param  array<int, array<string, mixed>>  $campsites
     */
    protected function syncCampsites(Trip $trip, array $campsites): void
    {
        $keptIds = [];

        foreach (array_values($campsites) as $order => $data) {
            $campsite = $trip->campsites()->updateOrCreate(
                ['id' => $data['id'] ?? null],
                [
                    'order' => $order,
                    'name' => $data['name'],
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'nights' => $data['nights'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ],
            );

            $keptIds[] = $campsite->id;
        }

        $trip->campsites()->whereNotIn('id', $keptIds)->delete();
    }
}
