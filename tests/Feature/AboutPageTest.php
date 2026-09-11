<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\File;
use App\Models\Image;
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

    public function test_the_page_renders_with_no_data_at_all(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('partners', 0)->has('timeline', 0));
    }
}
