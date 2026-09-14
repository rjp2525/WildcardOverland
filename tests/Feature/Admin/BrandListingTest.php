<?php

namespace Tests\Feature\Admin;

use App\Enums\ImageType;
use App\Models\Brand;
use App\Models\File;
use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The admin lists every brand; the homepage only shows the ones with a
 * public logo. Those two can disagree, and when they do the admin is the
 * one that has to show more, never less.
 */
class BrandListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'assets-test']);
        Storage::fake('assets-test');
        $this->actingAs(User::factory()->create());
    }

    protected function logo(string $name, bool $private = false): Image
    {
        $file = File::create([
            'name' => $name, 'original_filename' => "{$name}.png", 'original_extension' => 'png',
            'mime' => 'image/png', 'hash' => hash('sha256', $name), 'type' => 'static',
            'size' => 100, 'stored_path' => "uploads/{$name}.png", 'disk' => 'assets-test',
        ]);

        return Image::create([
            'name' => $name, 'type' => ImageType::Logo, 'file_id' => $file->id, 'private' => $private,
        ]);
    }

    public function test_the_admin_lists_brands_with_and_without_a_logo(): void
    {
        Brand::create(['name' => 'Has a logo', 'logo_image_id' => $this->logo('one')->id]);
        Brand::create(['name' => 'No logo at all']);

        $this->get(route('admin.brands.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/brands/Index')
                ->has('brands.data', 2));
    }

    public function test_a_brand_whose_image_row_vanished_is_still_listed(): void
    {
        $logo = $this->logo('orphan');
        $brand = Brand::create(['name' => 'Orphaned logo', 'logo_image_id' => $logo->id]);
        $logo->delete();

        $this->get(route('admin.brands.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('brands.data', 1)
                ->where('brands.data.0.name', 'Orphaned logo')
                ->where('brands.data.0.logo', null));

        $this->assertNotNull($brand->fresh());
    }

    public function test_the_logo_picker_offers_logos_not_every_photograph(): void
    {
        $logo = $this->logo('a-wordmark');
        $photo = $this->logo('a-campsite');
        $photo->update(['type' => ImageType::Photo]);

        $this->get(route('admin.brands.create'))
            ->assertInertia(fn ($page) => $page
                ->has('images', 1)
                ->where('images.0.value', $logo->id));
    }

    public function test_editing_a_brand_keeps_whatever_logo_it_already_has(): void
    {
        $odd = $this->logo('assigned-but-a-photo');
        $odd->update(['type' => ImageType::Photo]);
        $brand = Brand::create(['name' => 'Old assignment', 'logo_image_id' => $odd->id]);

        // Filtered out of the list in general, but not out of this brand's.
        $this->get(route('admin.brands.edit', $brand))
            ->assertInertia(fn ($page) => $page
                ->where('brand.logo_image_id', $odd->id)
                ->has('images', 1)
                ->where('images.0.value', $odd->id));
    }

    public function test_the_homepage_only_shows_brands_with_a_public_logo(): void
    {
        Brand::create(['name' => 'Public', 'logo_image_id' => $this->logo('pub')->id]);
        Brand::create(['name' => 'Private', 'logo_image_id' => $this->logo('priv', private: true)->id]);
        Brand::create(['name' => 'Logoless']);

        $this->get(route('homepage'))
            ->assertInertia(fn ($page) => $page
                ->has('partners', 1)
                ->where('partners.0.name', 'Public'));
    }
}
