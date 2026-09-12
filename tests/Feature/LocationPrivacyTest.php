<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use App\Support\LocationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Precise campsite coordinates are the part that will sit behind a
 * subscription. These tests pin down the two things that actually matter:
 * that an exact position never reaches an unentitled viewer's page source,
 * and that what they get instead is wrong in a stable, useful way.
 */
class LocationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function tripWithCampsite(float $lat = 42.5436, float $lng = -118.5320): Trip
    {
        $trip = Trip::create([
            'name' => 'Alvord', 'slug' => 'alvord',
            'is_draft' => false, 'published_at' => now()->subDay(),
        ]);

        $trip->campsites()->create([
            'order' => 0, 'name' => 'Hot Springs', 'state' => 'Oregon',
            'latitude' => $lat, 'longitude' => $lng,
        ]);

        return $trip;
    }

    public function test_a_guest_gets_the_campsite_but_not_its_coordinates(): void
    {
        $trip = $this->tripWithCampsite();

        $response = $this->get(route('trips.show', $trip->slug));

        $response->assertInertia(fn ($page) => $page
            ->where('trip.campsites.0.name', 'Hot Springs')
            ->where('trip.campsites.0.state', 'Oregon')
            ->where('trip.campsites.0.coordinates', null)
            ->where('trip.hasHiddenLocations', true));

        // Belt and braces: the number must not be anywhere in the response.
        $response->assertDontSee('42.5436')->assertDontSee('-118.532');
    }

    public function test_a_signed_in_viewer_gets_the_exact_coordinates(): void
    {
        $trip = $this->tripWithCampsite();

        $this->actingAs(User::factory()->create())
            ->get(route('trips.show', $trip->slug))
            ->assertInertia(fn ($page) => $page
                ->where('trip.campsites.0.coordinates.lat', 42.5436)
                ->where('trip.campsites.0.coordinates.lng', -118.532)
                ->where('trip.hasHiddenLocations', false));
    }

    public function test_a_trip_without_coordinates_does_not_claim_to_be_hiding_any(): void
    {
        $trip = Trip::create([
            'name' => 'No pins', 'slug' => 'no-pins',
            'is_draft' => false, 'published_at' => now()->subDay(),
        ]);
        $trip->campsites()->create(['order' => 0, 'name' => 'Somewhere']);

        $this->get(route('trips.show', $trip->slug))
            ->assertInertia(fn ($page) => $page->where('trip.hasHiddenLocations', false));
    }

    public function test_map_pins_are_fuzzed_for_a_guest_and_exact_for_a_member(): void
    {
        $trip = $this->tripWithCampsite();
        $campsite = $trip->campsites()->first();

        $this->get(route('homepage'))
            ->assertInertia(function ($page) use ($campsite) {
                $point = $page->toArray()['props']['campsites'][0];

                $this->assertFalse($point['precise']);
                $this->assertNotEqualsWithDelta((float) $campsite->latitude, $point['lat'], 0.0001);
                // Close enough to show the right region, far enough to be useless.
                $this->assertEqualsWithDelta((float) $campsite->latitude, $point['lat'], 0.08);
                $this->assertEqualsWithDelta((float) $campsite->longitude, $point['lng'], 0.08);
            });

        $this->actingAs(User::factory()->create())
            ->get(route('homepage'))
            ->assertInertia(fn ($page) => $page
                ->where('campsites.0.precise', true)
                ->where('campsites.0.lat', 42.5436));
    }

    public function test_the_same_campsite_is_always_fuzzed_to_the_same_place(): void
    {
        $campsite = $this->tripWithCampsite()->campsites()->first();

        $first = LocationAccess::position($campsite);
        $second = LocationAccess::position($campsite);

        $this->assertSame($first, $second);
        $this->assertFalse($first['precise']);
    }

    public function test_two_campsites_are_not_offset_in_the_same_direction(): void
    {
        $trip = $this->tripWithCampsite();
        $other = $trip->campsites()->create([
            'order' => 1, 'name' => 'Playa', 'latitude' => 42.5436, 'longitude' => -118.5320,
        ]);

        $this->assertNotSame(
            LocationAccess::position($trip->campsites()->first()),
            LocationAccess::position($other),
        );
    }
}
