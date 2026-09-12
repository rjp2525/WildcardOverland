<?php

namespace Tests\Feature\Admin;

use App\Enums\ImageType;
use App\Models\File;
use App\Models\Image;
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

    protected function makeImage(string $name): Image
    {
        $file = File::create([
            'name' => $name, 'original_filename' => "{$name}.png", 'original_extension' => 'png',
            'mime' => 'image/png', 'hash' => hash('sha256', $name), 'type' => 'content',
            'size' => 100, 'stored_path' => "uploads/{$name}.png", 'disk' => 'local',
        ]);

        return Image::create(['name' => $name, 'type' => ImageType::Photo, 'file_id' => $file->id]);
    }

    public function test_a_trip_keeps_an_ordered_gallery_with_captions(): void
    {
        $trip = Trip::create(['name' => 'Baja', 'slug' => 'baja']);
        $first = $this->makeImage('one');
        $second = $this->makeImage('two');

        $this->put(route('admin.trips.update', $trip), [
            'name' => 'Baja',
            'slug' => 'baja',
            'hero_image_id' => $first->id,
            'images' => [
                ['id' => $second->id, 'caption' => 'Dunes'],
                ['id' => $first->id, 'caption' => 'Camp'],
            ],
        ])->assertRedirect();

        $trip->refresh();

        $this->assertSame($first->id, $trip->hero_image_id);
        // Submitted order wins, not id order.
        $this->assertSame([$second->id, $first->id], $trip->images->pluck('id')->all());
        $this->assertSame('Dunes', $trip->images->first()->pivot->caption);
    }

    public function test_removing_a_gallery_row_detaches_it(): void
    {
        $trip = Trip::create(['name' => 'Baja', 'slug' => 'baja']);
        $keep = $this->makeImage('keep');
        $drop = $this->makeImage('drop');
        $trip->images()->sync([$keep->id => ['order' => 0], $drop->id => ['order' => 1]]);

        $this->put(route('admin.trips.update', $trip), [
            'name' => 'Baja', 'slug' => 'baja',
            'images' => [['id' => $keep->id, 'caption' => null]],
        ])->assertRedirect();

        $this->assertSame([$keep->id], $trip->fresh()->images->pluck('id')->all());
        // Detaching from a trip must not delete the image itself.
        $this->assertSame(2, Image::count());
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
