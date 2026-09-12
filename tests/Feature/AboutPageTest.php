<?php

namespace Tests\Feature;

use App\Enums\ImageType;
use App\Models\Brand;
use App\Models\File;
use App\Models\Image;
use App\Models\Trip;
use App\Models\VehicleModification;
use App\Support\AssetUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'assets-test']);
        Storage::fake('assets-test');
        Storage::fake('local');
    }

    /**
     * Store a real image and return the brand that uses it as its logo.
     */
    protected function brandWithLogo(string $name, bool $private = false): Brand
    {
        $upload = UploadedFile::fake()->image("{$name}.png", 120, 60);
        $path = "uploads/{$name}.png";
        Storage::disk('assets-test')->put($path, file_get_contents($upload->getRealPath()));

        $file = File::create([
            'name' => $name,
            'original_filename' => "{$name}.png",
            'original_extension' => 'png',
            'mime' => 'image/png',
            'hash' => hash('sha256', $name),
            'type' => 'static',
            'size' => 1024,
            'stored_path' => $path,
            'disk' => 'assets-test',
        ]);

        $image = Image::create([
            'name' => $name,
            'type' => ImageType::Logo,
            'file_id' => $file->id,
            'width' => 120,
            'height' => 60,
            'private' => $private,
        ]);

        return Brand::create(['name' => $name, 'logo_image_id' => $image->id]);
    }

    public function test_partners_come_from_brands_that_have_a_logo(): void
    {
        $this->brandWithLogo('katadyn');
        Brand::create(['name' => 'No logo brand']);

        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('About')
                ->has('partners', 1)
                ->where('partners.0.name', 'katadyn')
                ->where('partners.0.width', 120));
    }

    public function test_private_logos_are_never_shown_publicly(): void
    {
        $this->brandWithLogo('secret', private: true);

        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page->has('partners', 0));
    }

    public function test_a_partner_logo_url_is_signed_and_serves_the_image(): void
    {
        $brand = $this->brandWithLogo('katadyn');

        $url = AssetUrl::image($brand->logo->file, 300, 146);

        // The signature must satisfy the asset route it was generated for.
        $response = $this->get($url);
        $response->assertOk();
        $this->assertStringStartsWith('image/', (string) $response->headers->get('content-type'));
    }

    public function test_a_tampered_signature_is_rejected(): void
    {
        $brand = $this->brandWithLogo('katadyn');

        $url = AssetUrl::image($brand->logo->file, 300, 146);
        $tampered = preg_replace('/s=[a-f0-9]+/', 's='.str_repeat('0', 32), $url);

        $this->get($tampered)->assertForbidden();
    }

    public function test_changing_a_signed_parameter_is_rejected(): void
    {
        $brand = $this->brandWithLogo('katadyn');

        // Same signature, different width: must not validate.
        $url = str_replace('w=300', 'w=600', AssetUrl::image($brand->logo->file, 300, 146));

        $this->get($url)->assertForbidden();
    }

    public function test_unsupported_dimensions_fail_loudly_at_the_call_site(): void
    {
        $brand = $this->brandWithLogo('katadyn');

        $this->expectException(\InvalidArgumentException::class);

        AssetUrl::image($brand->logo->file, 12345, 146);
    }

    public function test_the_timeline_only_includes_modifications_flagged_for_it(): void
    {
        VehicleModification::create(['name' => 'Shown', 'shown_on_timeline' => true]);
        VehicleModification::create(['name' => 'Hidden', 'shown_on_timeline' => false]);

        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page
                ->has('timeline', 1)
                ->where('timeline.0.name', 'Shown'));
    }

    public function test_the_timeline_runs_newest_first_with_undated_entries_last(): void
    {
        VehicleModification::create(['name' => 'Older', 'install_date' => '2025-03-14', 'shown_on_timeline' => true]);
        VehicleModification::create(['name' => 'Newer', 'install_date' => '2025-07-02', 'shown_on_timeline' => true]);
        VehicleModification::create(['name' => 'Undated', 'shown_on_timeline' => true]);

        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page
                ->where('timeline.0.name', 'Newer')
                ->where('timeline.1.name', 'Older')
                ->where('timeline.2.name', 'Undated')
                ->where('timeline.0.installed_label', 'Jul 2025')
                ->where('timeline.2.installed_label', null));
    }

    public function test_the_timeline_does_not_expose_cost(): void
    {
        VehicleModification::create([
            'name' => 'Roof Rack',
            'cost' => 124999,
            'shown_on_timeline' => true,
        ]);

        $response = $this->get(route('about'));

        $response->assertInertia(fn ($page) => $page->missing('timeline.0.cost'));
        $response->assertDontSee('124999');
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

    public function test_the_counters_come_from_published_trips(): void
    {
        $this->trip('One', ['start_date' => '2026-03-01', 'end_date' => '2026-03-05']);
        $this->trip('Two', ['start_date' => '2026-04-01', 'end_date' => '2026-04-03']);

        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.trips', 2)
                // TripObserver derives 4 + 2 nights from those date ranges.
                ->where('stats.nights', 6));
    }

    public function test_drafts_and_future_posts_are_excluded_from_the_counters(): void
    {
        $this->trip('Live', ['start_date' => '2026-03-01', 'end_date' => '2026-03-05']);
        $this->trip('Draft', ['is_draft' => true, 'start_date' => '2026-03-01', 'end_date' => '2026-03-09']);
        $this->trip('Scheduled', ['published_at' => now()->addWeek(), 'start_date' => '2026-03-01', 'end_date' => '2026-03-09']);
        $this->trip('Never published', ['published_at' => null, 'start_date' => '2026-03-01', 'end_date' => '2026-03-09']);

        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.trips', 1)
                ->where('stats.nights', 4));
    }

    public function test_deleted_trips_do_not_count(): void
    {
        $this->trip('Gone', ['start_date' => '2026-03-01', 'end_date' => '2026-03-05'])->delete();

        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.trips', 0)
                ->where('stats.nights', 0));
    }

    public function test_the_counters_are_zero_rather_than_null_when_empty(): void
    {
        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.trips', 0)
                ->where('stats.nights', 0));
    }

    protected function image(string $name, ImageType $type, bool $private = false): Image
    {
        $file = File::create([
            'name' => $name,
            'original_filename' => "{$name}.png",
            'original_extension' => 'png',
            'mime' => 'image/png',
            'hash' => hash('sha256', $name),
            'type' => 'content',
            'size' => 512,
            'stored_path' => "uploads/{$name}.png",
            'disk' => 'assets-test',
        ]);

        return Image::create([
            'name' => $name,
            'type' => $type,
            'file_id' => $file->id,
            'private' => $private,
        ]);
    }

    public function test_the_photo_counter_only_counts_photographs(): void
    {
        $this->image('sunset', ImageType::Photo);
        $this->image('camp', ImageType::Photo);
        $this->image('partner-mark', ImageType::Logo);
        $this->image('divider', ImageType::Graphic);

        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page->where('stats.photos', 2));
    }

    public function test_private_photographs_are_not_counted(): void
    {
        $this->image('public-shot', ImageType::Photo);
        $this->image('private-shot', ImageType::Photo, private: true);

        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page->where('stats.photos', 1));
    }

    public function test_a_seeded_partner_logo_does_not_inflate_the_photo_count(): void
    {
        $this->brandWithLogo('katadyn');

        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page
                ->has('partners', 1)
                ->where('stats.photos', 0));
    }

    public function test_miles_come_from_published_trips(): void
    {
        $this->trip('One', ['miles' => 400]);
        $this->trip('Two', ['miles' => 120]);
        $this->trip('Draft', ['is_draft' => true, 'miles' => 9999]);
        $this->trip('No mileage recorded');

        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page->where('stats.miles', 520));
    }

    public function test_states_counts_distinct_regions_from_published_trips(): void
    {
        $live = $this->trip('Live');
        $live->campsites()->createMany([
            ['order' => 0, 'name' => 'A', 'latitude' => 1, 'longitude' => 1, 'state' => 'Utah'],
            ['order' => 1, 'name' => 'B', 'latitude' => 2, 'longitude' => 2, 'state' => 'Utah'],
            ['order' => 2, 'name' => 'C', 'latitude' => 3, 'longitude' => 3, 'state' => 'Colorado'],
            // Never resolved, so it contributes nothing.
            ['order' => 3, 'name' => 'D', 'latitude' => 4, 'longitude' => 4],
        ]);

        $draft = $this->trip('Draft', ['is_draft' => true]);
        $draft->campsites()->create(['order' => 0, 'name' => 'Hidden', 'latitude' => 5, 'longitude' => 5, 'state' => 'Nevada']);

        $this->get(route('about'))
            ->assertInertia(fn ($page) => $page->where('stats.states', 2));
    }

    public function test_the_page_renders_with_no_data_at_all(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('partners', 0)->has('timeline', 0));
    }
}
