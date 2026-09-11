<?php

namespace Tests\Feature;

use App\Enums\ImageType;
use App\Models\Brand;
use App\Models\File;
use App\Models\Image;
use Database\Seeders\PartnerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'assets-test']);
        Storage::fake('assets-test');
    }

    public function test_it_seeds_the_previously_hard_coded_partners(): void
    {
        $this->seed(PartnerSeeder::class);

        $this->assertSame(3, Brand::count());
        $this->assertNotNull(Brand::firstWhere('name', 'Katadyn Switzerland')?->logo_image_id);

        foreach (File::all() as $file) {
            Storage::disk('assets-test')->assertExists($file->stored_path);
        }
    }

    public function test_seeded_logos_are_typed_as_logos_not_photographs(): void
    {
        $this->seed(PartnerSeeder::class);

        $this->assertSame(3, Image::where('type', ImageType::Logo)->count());
        $this->assertSame(0, Image::publicPhotos()->count());
    }

    public function test_it_can_be_run_twice_without_duplicating_anything(): void
    {
        $this->seed(PartnerSeeder::class);
        $this->seed(PartnerSeeder::class);

        $this->assertSame(3, Brand::count());
        // Uploads deduplicate on hash, so no second copy of each logo.
        $this->assertSame(3, File::count());
        $this->assertSame(3, Image::count());
    }
}
