<?php

namespace Tests\Feature;

use App\Models\Campsite;
use App\Models\File;
use App\Models\Image;
use App\Models\Recipe;
use App\Models\Trip;
use App\Models\VehicleModification;
use App\Support\SiteContent;
use Database\Seeders\Demo\DemoContent;
use Database\Seeders\DemoContentCleanupSeeder;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoContentSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'assets-test']);
        Storage::fake('assets-test');
    }

    public function test_it_seeds_a_full_set_of_content(): void
    {
        $this->seed(DemoContentSeeder::class);

        $this->assertSame(count(DemoContent::trips()), Trip::count());
        $this->assertSame(count(DemoContent::recipes()), Recipe::count());
        $this->assertSame(count(DemoContent::modifications()), VehicleModification::count());
        $this->assertSame(count(DemoContent::imageKeys()), Image::count());

        // Every trip is published, has a hero and at least one campsite.
        $this->assertSame(Trip::count(), Trip::published()->count());
        $this->assertSame(0, Trip::whereNull('hero_image_id')->count());
        $this->assertGreaterThan(0, Campsite::count());
    }

    public function test_seeded_campsites_carry_a_state_without_geocoding(): void
    {
        $this->seed(DemoContentSeeder::class);

        // The base TestCase disables geocoding and blocks stray requests, so
        // these states can only have come from the seed data itself.
        $this->assertSame(0, Campsite::whereNull('state')->count());
        $this->assertGreaterThanOrEqual(6, Campsite::distinct()->count('state'));
    }

    public function test_it_populates_every_statistic(): void
    {
        $this->seed(DemoContentSeeder::class);

        $stats = SiteContent::stats();

        foreach (['trips', 'nights', 'miles', 'photos', 'states'] as $key) {
            $this->assertGreaterThan(0, $stats[$key], "{$key} should not be zero after seeding");
        }
    }

    public function test_running_it_twice_changes_nothing(): void
    {
        $this->seed(DemoContentSeeder::class);
        $counts = [Trip::count(), Recipe::count(), File::count(), Image::count(), Campsite::count()];

        $this->seed(DemoContentSeeder::class);

        $this->assertSame($counts, [Trip::count(), Recipe::count(), File::count(), Image::count(), Campsite::count()]);
    }

    public function test_cleanup_removes_exactly_what_was_seeded(): void
    {
        $this->seed(DemoContentSeeder::class);
        $this->seed(DemoContentCleanupSeeder::class);

        $this->assertSame(0, Trip::withTrashed()->count());
        $this->assertSame(0, Recipe::withTrashed()->count());
        $this->assertSame(0, VehicleModification::count());
        $this->assertSame(0, Campsite::count());
        $this->assertSame(0, File::withTrashed()->count());
        $this->assertSame(0, Image::count());
    }

    public function test_cleanup_leaves_content_you_wrote_yourself_alone(): void
    {
        $this->seed(DemoContentSeeder::class);

        $mine = Trip::create(['name' => 'My Trip', 'slug' => 'my-trip', 'is_draft' => false, 'published_at' => now()]);
        $myMod = VehicleModification::create(['name' => 'My Own Mod']);

        $this->seed(DemoContentCleanupSeeder::class);

        $this->assertModelExists($mine);
        $this->assertModelExists($myMod);
        $this->assertSame(1, Trip::count());
        $this->assertSame(1, VehicleModification::count());
    }

    public function test_cleanup_is_safe_to_run_when_nothing_was_seeded(): void
    {
        $this->seed(DemoContentCleanupSeeder::class);

        $this->assertSame(0, Trip::count());
    }
}
