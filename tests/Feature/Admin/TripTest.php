<?php

namespace Tests\Feature\Admin;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_the_index_lists_trips(): void
    {
        Trip::create(['name' => 'Baja', 'slug' => 'baja']);

        $this->get(route('admin.trips.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/trips/Index')
                ->has('trips.data', 1)
                ->where('trips.data.0.name', 'Baja'));
    }

    public function test_a_trip_is_created_with_its_campsites(): void
    {
        $this->post(route('admin.trips.store'), [
            'name' => 'Mojave Loop',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-05',
            'is_draft' => true,
            'campsites' => [
                ['name' => 'Afton Canyon', 'nights' => 2, 'latitude' => 35.03, 'longitude' => -116.38],
                ['name' => 'Kelso Dunes', 'nights' => 1],
            ],
        ])->assertRedirect();

        $trip = Trip::firstWhere('name', 'Mojave Loop');

        $this->assertNotNull($trip);
        // Slug is derived from the name when omitted.
        $this->assertSame('mojave-loop', $trip->slug);
        // TripObserver derives the nights from the date range.
        $this->assertSame(4, $trip->calculated_nights);
        $this->assertSame(['Afton Canyon', 'Kelso Dunes'], $trip->campsites->pluck('name')->all());
        $this->assertSame([0, 1], $trip->campsites->pluck('order')->all());
    }

    public function test_validation_rejects_a_bad_trip(): void
    {
        $this->post(route('admin.trips.store'), [
            'name' => '',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-01',
            'campsites' => [['name' => '', 'latitude' => 999]],
        ])->assertSessionHasErrors([
            'name',
            'end_date',
            'campsites.0.name',
            'campsites.0.latitude',
        ]);
    }

    public function test_updating_syncs_campsites_and_drops_removed_ones(): void
    {
        $trip = Trip::create(['name' => 'Baja', 'slug' => 'baja']);
        $keep = $trip->campsites()->create(['name' => 'Gonzaga', 'order' => 0]);
        $drop = $trip->campsites()->create(['name' => 'Remove me', 'order' => 1]);

        $this->put(route('admin.trips.update', $trip), [
            'name' => 'Baja 2026',
            'slug' => 'baja',
            'campsites' => [
                ['id' => $keep->id, 'name' => 'Gonzaga Bay'],
                ['name' => 'Brand new stop'],
            ],
        ])->assertRedirect();

        $this->assertSame('Baja 2026', $trip->fresh()->name);
        $this->assertDatabaseMissing('campsites', ['id' => $drop->id]);
        $this->assertSame('Gonzaga Bay', $keep->fresh()->name);
        $this->assertSame(2, $trip->campsites()->count());
    }

    public function test_slugs_must_be_unique_but_a_trip_may_keep_its_own(): void
    {
        Trip::create(['name' => 'Taken', 'slug' => 'taken']);
        $trip = Trip::create(['name' => 'Mine', 'slug' => 'mine']);

        $this->put(route('admin.trips.update', $trip), ['name' => 'Mine', 'slug' => 'taken'])
            ->assertSessionHasErrors('slug');

        $this->put(route('admin.trips.update', $trip), ['name' => 'Mine Renamed', 'slug' => 'mine'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_trip_is_soft_deleted(): void
    {
        $trip = Trip::create(['name' => 'Baja', 'slug' => 'baja']);

        $this->delete(route('admin.trips.destroy', $trip))->assertRedirect();

        $this->assertSoftDeleted($trip);
    }

    public function test_the_sort_column_is_whitelisted(): void
    {
        Trip::create(['name' => 'Baja', 'slug' => 'baja']);

        // An unlisted column must be ignored rather than reaching the query.
        $this->get(route('admin.trips.index', ['sort' => 'name); drop table trips --']))
            ->assertOk();

        $this->assertDatabaseCount('trips', 1);
    }
}
