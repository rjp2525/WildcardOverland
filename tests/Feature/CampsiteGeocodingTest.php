<?php

namespace Tests\Feature;

use App\Jobs\ReverseGeocodeCampsite;
use App\Models\Campsite;
use App\Models\Trip;
use App\Services\ReverseGeocoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampsiteGeocodingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The base TestCase disables geocoding; this suite is what tests it.
        config(['geocoding.enabled' => true, 'geocoding.throttle_seconds' => 0]);
    }

    protected function trip(): Trip
    {
        return Trip::create(['name' => 'Baja', 'slug' => 'baja', 'is_draft' => false, 'published_at' => now()->subDay()]);
    }

    protected function fakeNominatim(string $state = 'Oregon', string $country = 'us'): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'address' => ['state' => $state, 'country_code' => $country],
            ]),
        ]);
    }

    public function test_saving_a_campsite_with_coordinates_queues_a_lookup(): void
    {
        Queue::fake();

        $this->trip()->campsites()->create(['name' => 'Playa', 'latitude' => 42.5, 'longitude' => -118.5]);

        Queue::assertPushed(ReverseGeocodeCampsite::class);
    }

    public function test_a_campsite_without_coordinates_queues_nothing(): void
    {
        Queue::fake();

        $this->trip()->campsites()->create(['name' => 'Somewhere']);

        Queue::assertNothingPushed();
    }

    public function test_editing_unrelated_fields_does_not_re_geocode(): void
    {
        $campsite = $this->trip()->campsites()->create([
            'name' => 'Playa', 'latitude' => 42.5, 'longitude' => -118.5,
            'state' => 'Oregon', 'geocoded_at' => now(),
        ]);

        Queue::fake();
        $campsite->update(['notes' => 'Windy']);

        Queue::assertNothingPushed();
    }

    public function test_moving_a_campsite_re_geocodes_it(): void
    {
        $campsite = $this->trip()->campsites()->create([
            'name' => 'Playa', 'latitude' => 42.5, 'longitude' => -118.5,
            'state' => 'Oregon', 'geocoded_at' => now(),
        ]);

        Queue::fake();
        $campsite->update(['latitude' => 38.4]);

        Queue::assertPushed(ReverseGeocodeCampsite::class);
    }

    public function test_the_job_stores_the_resolved_region(): void
    {
        $this->fakeNominatim('Utah');

        $campsite = Campsite::withoutEvents(fn () => $this->trip()->campsites()->create([
            'name' => 'Murphy Hogback', 'latitude' => 38.37, 'longitude' => -109.92,
        ]));

        (new ReverseGeocodeCampsite($campsite))->handle(app(ReverseGeocoder::class));

        $campsite->refresh();
        $this->assertSame('Utah', $campsite->state);
        $this->assertSame('US', $campsite->country_code);
        $this->assertNotNull($campsite->geocoded_at);
    }

    public function test_a_failed_lookup_leaves_the_campsite_alone_so_it_can_be_retried(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response(status: 503)]);

        $campsite = Campsite::withoutEvents(fn () => $this->trip()->campsites()->create([
            'name' => 'Nowhere', 'latitude' => 1.0, 'longitude' => 2.0,
        ]));

        (new ReverseGeocodeCampsite($campsite))->handle(app(ReverseGeocoder::class));

        $campsite->refresh();
        $this->assertNull($campsite->state);
        $this->assertNull($campsite->geocoded_at);
    }

    public function test_a_lookup_that_finds_no_region_does_not_wipe_a_known_state(): void
    {
        // A point at sea: the request succeeds, the address has no state.
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response(['address' => []])]);

        $campsite = Campsite::withoutEvents(fn () => $this->trip()->campsites()->create([
            'name' => 'Seeded', 'latitude' => 1.0, 'longitude' => 1.0, 'state' => 'Utah',
        ]));

        (new ReverseGeocodeCampsite($campsite))->handle(app(ReverseGeocoder::class));

        $campsite->refresh();
        $this->assertSame('Utah', $campsite->state);
        // Stamped, so it is not retried forever.
        $this->assertNotNull($campsite->geocoded_at);
    }

    public function test_geocoding_can_be_switched_off_entirely(): void
    {
        config(['geocoding.enabled' => false]);
        Http::fake();
        Queue::fake();

        $this->trip()->campsites()->create(['name' => 'Playa', 'latitude' => 42.5, 'longitude' => -118.5]);

        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_the_backfill_command_resolves_campsites_missing_a_state(): void
    {
        $this->fakeNominatim('Colorado');

        $trip = $this->trip();
        Campsite::withoutEvents(function () use ($trip) {
            $trip->campsites()->create(['name' => 'A', 'latitude' => 37.9, 'longitude' => -107.6]);
            $trip->campsites()->create(['name' => 'B', 'latitude' => 37.8, 'longitude' => -107.7, 'state' => 'Already', 'geocoded_at' => now()]);
            $trip->campsites()->create(['name' => 'C']);
        });

        $this->artisan('campsites:geocode')->assertSuccessful();

        $this->assertSame('Colorado', Campsite::where('name', 'A')->value('state'));
        // Untouched: already resolved, and no coordinates respectively.
        $this->assertSame('Already', Campsite::where('name', 'B')->value('state'));
        $this->assertNull(Campsite::where('name', 'C')->value('state'));
    }
}
