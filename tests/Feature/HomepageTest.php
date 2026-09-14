<?php

namespace Tests\Feature;

use App\Enums\ImageType;
use App\Enums\MealType;
use App\Models\File;
use App\Models\Image;
use App\Models\Recipe;
use App\Models\Trip;
use App\Models\VehicleModification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'assets-test']);
        Storage::fake('assets-test');
    }

    protected function image(string $name, bool $featured = false, bool $private = false): Image
    {
        $file = File::create([
            'name' => $name, 'original_filename' => "{$name}.png", 'original_extension' => 'png',
            'mime' => 'image/png', 'hash' => hash('sha256', $name), 'type' => 'content',
            'size' => 100, 'stored_path' => "uploads/{$name}.png", 'disk' => 'assets-test',
        ]);

        return Image::create([
            'name' => $name, 'type' => ImageType::Photo, 'file_id' => $file->id,
            'featured' => $featured, 'private' => $private, 'caption' => "{$name} caption",
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function trip(string $name, array $attributes = []): Trip
    {
        return Trip::create([
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'is_draft' => false,
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    public function test_the_homepage_renders_with_no_content_at_all(): void
    {
        $this->get(route('homepage'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Homepage')
                ->where('latestTrip', null)
                ->where('hasRecipes', false)
                ->has('trips', 0)
                ->has('recipes', 0)
                ->has('gallery', 0)
                ->has('campsites', 0)
                ->where('stats.trips', 0));
    }

    public function test_the_hero_points_at_the_most_recent_published_trip(): void
    {
        $this->trip('Older', ['start_date' => '2026-01-01']);
        $newest = $this->trip('Newest', ['start_date' => '2026-08-01']);
        $this->trip('Draft', ['is_draft' => true, 'start_date' => '2026-12-01']);

        $this->get(route('homepage'))
            ->assertInertia(fn ($page) => $page
                ->where('latestTrip.name', 'Newest')
                ->where('latestTrip.url', route('trips.show', $newest->slug)));
    }

    public function test_the_recipes_cta_only_shows_when_a_recipe_is_published(): void
    {
        $this->get(route('homepage'))
            ->assertInertia(fn ($page) => $page->where('hasRecipes', false));

        Recipe::create([
            'name' => 'Chili', 'slug' => 'chili', 'meal_type' => MealType::Dinner,
            'is_draft' => false, 'published_at' => now()->subDay(),
        ]);

        $this->get(route('homepage'))
            ->assertInertia(fn ($page) => $page->where('hasRecipes', true));
    }

    public function test_the_gallery_only_shows_featured_public_photographs(): void
    {
        $this->image('featured-shot', featured: true);
        $this->image('not-featured');
        $this->image('featured-but-private', featured: true, private: true);

        $logo = $this->image('featured-logo', featured: true);
        $logo->update(['type' => ImageType::Logo]);

        $this->get(route('homepage'))
            ->assertInertia(fn ($page) => $page
                ->has('gallery', 1)
                ->where('gallery.0.caption', 'featured-shot caption'));
    }

    public function test_the_gallery_respects_sort_order(): void
    {
        $this->image('second', featured: true)->update(['sort_order' => 2]);
        $this->image('first', featured: true)->update(['sort_order' => 1]);

        $this->get(route('homepage'))
            ->assertInertia(fn ($page) => $page
                ->where('gallery.0.caption', 'first caption')
                ->where('gallery.1.caption', 'second caption'));
    }

    public function test_map_points_come_only_from_published_trips_with_coordinates(): void
    {
        $published = $this->trip('Baja');
        $published->campsites()->create(['order' => 0, 'name' => 'With coords', 'latitude' => 29.8, 'longitude' => -114.4]);
        $published->campsites()->create(['order' => 1, 'name' => 'No coords']);

        $draft = $this->trip('Secret', ['is_draft' => true]);
        $draft->campsites()->create(['order' => 0, 'name' => 'Hidden', 'latitude' => 1.0, 'longitude' => 2.0]);

        $this->get(route('homepage'))
            ->assertInertia(fn ($page) => $page
                ->has('campsites', 1)
                ->where('campsites.0.name', 'With coords')
                ->where('campsites.0.trip', 'Baja')
                // The pin itself is fuzzed for a guest; LocationPrivacyTest
                // covers how far, and for whom.
                ->where('campsites.0.precise', false));
    }

    public function test_the_rig_teaser_is_limited_and_ordered(): void
    {
        foreach (['A' => '2025-01-01', 'B' => '2025-02-01', 'C' => '2025-03-01', 'D' => '2025-04-01', 'E' => '2025-05-01'] as $name => $date) {
            VehicleModification::create(['name' => $name, 'install_date' => $date, 'shown_on_timeline' => true]);
        }
        VehicleModification::create(['name' => 'Hidden', 'shown_on_timeline' => false]);

        $this->get(route('homepage'))
            ->assertInertia(fn ($page) => $page
                ->has('modifications', 4)
                ->where('modifications.0.name', 'E'));
    }

    public function test_only_three_trips_and_recipes_are_teased(): void
    {
        foreach (range(1, 5) as $i) {
            $this->trip("Trip {$i}", ['start_date' => "2026-0{$i}-01"]);
        }

        $this->get(route('homepage'))
            ->assertInertia(fn ($page) => $page->has('trips', 3));
    }

    public function test_an_empty_site_says_so_rather_than_going_blank(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('trips', [])
                ->where('recipes', [])
                ->where('campsites', []));
    }

    public function test_the_theme_is_settled_before_the_page_paints(): void
    {
        /*
         * Applying it from a Vue component flashed the wrong theme on every
         * load and never ran at all in the admin, which has no navbar.
         */
        $this->get('/')
            ->assertOk()
            ->assertSee("localStorage.getItem('theme')", escape: false)
            ->assertSee("classList.toggle('dark'", escape: false);
    }
}
