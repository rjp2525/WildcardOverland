<?php

namespace Tests\Feature\Admin;

use App\Models\Campsite;
use App\Models\Recipe;
use App\Models\Trip;
use App\Models\User;
use App\Support\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deleting has two settings. Trash takes something off the site but keeps it
 * restorable; the permanent one is what clears the record and everything
 * hanging off it out of the database for good.
 */
class TrashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    protected function publishedTrip(string $name = 'Baja'): Trip
    {
        return Trip::create([
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);
    }

    public function test_a_trashed_trip_is_hidden_from_the_index_by_default(): void
    {
        $trip = $this->publishedTrip();
        $trip->delete();

        $this->get(route('admin.trips.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('trips.data', 0)->where('trashedCount', 1));
    }

    public function test_the_trash_tab_shows_it(): void
    {
        $trip = $this->publishedTrip();
        $trip->delete();

        $this->get(route('admin.trips.index', ['trashed' => 'only']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('trips.data', 1)
                ->where('trips.data.0.name', 'Baja')
                ->where('trips.data.0.deleted_at', $trip->fresh()->deleted_at->toDateTimeString()));
    }

    public function test_everything_shows_both(): void
    {
        $this->publishedTrip('Kept');
        $this->publishedTrip('Binned')->delete();

        $this->get(route('admin.trips.index', ['trashed' => 'with']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('trips.data', 2));
    }

    public function test_an_unknown_trashed_value_is_treated_as_live_only(): void
    {
        $this->publishedTrip('Binned')->delete();

        $this->get(route('admin.trips.index', ['trashed' => 'everything; drop table']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('trips.data', 0));
    }

    public function test_a_trashed_trip_takes_its_campsites_off_the_map(): void
    {
        $trip = $this->publishedTrip();
        Campsite::create([
            'trip_id' => $trip->id,
            'name' => 'Bahia Concepcion',
            'latitude' => 26.66,
            'longitude' => -111.85,
        ]);

        $this->assertCount(1, SiteContent::campsitePoints());

        $trip->delete();

        $this->assertSame([], SiteContent::campsitePoints());
    }

    public function test_restoring_a_trip_puts_its_campsites_back(): void
    {
        $trip = $this->publishedTrip();
        Campsite::create([
            'trip_id' => $trip->id,
            'name' => 'Bahia Concepcion',
            'latitude' => 26.66,
            'longitude' => -111.85,
        ]);
        $trip->delete();

        $this->put(route('admin.trips.restore', $trip->id))->assertRedirect();

        $this->assertNull($trip->fresh()->deleted_at);
        $this->assertCount(1, SiteContent::campsitePoints());
    }

    public function test_deleting_a_trip_for_good_takes_its_campsites_with_it(): void
    {
        $trip = $this->publishedTrip();
        Campsite::create([
            'trip_id' => $trip->id,
            'name' => 'Bahia Concepcion',
            'latitude' => 26.66,
            'longitude' => -111.85,
        ]);
        $trip->delete();

        $this->delete(route('admin.trips.force-destroy', $trip->id))->assertRedirect();

        $this->assertSame(0, Trip::withTrashed()->count());
        $this->assertSame(0, Campsite::count());
    }

    public function test_a_live_trip_can_be_deleted_for_good_without_trashing_it_first(): void
    {
        $trip = $this->publishedTrip();

        $this->delete(route('admin.trips.force-destroy', $trip->id))->assertRedirect();

        $this->assertSame(0, Trip::withTrashed()->count());
    }

    public function test_the_ordinary_delete_only_trashes(): void
    {
        $trip = $this->publishedTrip();

        $this->delete(route('admin.trips.destroy', $trip->id))->assertRedirect();

        $this->assertSame(1, Trip::withTrashed()->count());
        $this->assertSame(0, Trip::count());
    }

    public function test_recipes_trash_and_restore_the_same_way(): void
    {
        $recipe = Recipe::create(['name' => 'Skottle hash', 'slug' => 'skottle-hash']);
        $recipe->delete();

        $this->get(route('admin.recipes.index', ['trashed' => 'only']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('recipes.data', 1)->where('trashedCount', 1));

        $this->put(route('admin.recipes.restore', $recipe->id))->assertRedirect();

        $this->assertNull($recipe->fresh()->deleted_at);
    }

    public function test_a_recipe_can_be_deleted_for_good(): void
    {
        $recipe = Recipe::create(['name' => 'Skottle hash', 'slug' => 'skottle-hash']);
        $recipe->delete();

        $this->delete(route('admin.recipes.force-destroy', $recipe->id))->assertRedirect();

        $this->assertSame(0, Recipe::withTrashed()->count());
    }

    public function test_restore_and_force_are_behind_the_login(): void
    {
        auth()->logout();

        $trip = $this->publishedTrip();
        $trip->delete();

        $this->put(route('admin.trips.restore', $trip->id))->assertRedirect(route('admin.login'));
        $this->delete(route('admin.trips.force-destroy', $trip->id))->assertRedirect(route('admin.login'));

        $this->assertSame(1, Trip::withTrashed()->count());
    }
}
