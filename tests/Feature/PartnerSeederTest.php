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

    /**
     * Everyone the seeder knows about. Only TriPine ships a logo in the repo;
     * the rest get theirs uploaded through the admin.
     */
    protected array $expected = [
        'Tune Outdoor',
        'Redarc Electronics',
        'Devos Outdoor',
        'Dometic',
        'Midland Radio',
        'Baja Designs',
        'Falken Tires',
        'TriPine',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'assets-test']);
        Storage::fake('assets-test');
    }

    public function test_it_seeds_every_partner(): void
    {
        $this->seed(PartnerSeeder::class);

        $this->assertEqualsCanonicalizing($this->expected, Brand::pluck('name')->all());

        foreach (File::all() as $file) {
            Storage::disk('assets-test')->assertExists($file->stored_path);
        }
    }

    public function test_a_partner_without_a_bundled_logo_is_still_created(): void
    {
        $this->seed(PartnerSeeder::class);

        $dometic = Brand::firstWhere('name', 'Dometic');

        $this->assertNotNull($dometic);
        $this->assertNull($dometic->logo_image_id);
        $this->assertSame('https://www.dometic.com/en-us', $dometic->website);
    }

    public function test_a_bundled_logo_is_attached(): void
    {
        $this->seed(PartnerSeeder::class);

        $this->assertNotNull(Brand::firstWhere('name', 'TriPine')?->logo_image_id);
    }

    public function test_seeded_logos_are_typed_as_logos_not_photographs(): void
    {
        $this->seed(PartnerSeeder::class);

        $this->assertSame(1, Image::where('type', ImageType::Logo)->count());
        $this->assertSame(0, Image::publicPhotos()->count());
    }

    public function test_a_logo_attached_in_the_admin_is_left_alone(): void
    {
        $this->seed(PartnerSeeder::class);

        // Stand in for a logo uploaded through the admin.
        $uploaded = Image::where('type', ImageType::Logo)->firstOrFail();
        $dometic = Brand::firstWhere('name', 'Dometic');
        $dometic->update(['logo_image_id' => $uploaded->id]);

        $this->seed(PartnerSeeder::class);

        $this->assertSame($uploaded->id, $dometic->fresh()->logo_image_id);
    }

    public function test_partners_that_are_no_longer_partners_are_removed(): void
    {
        Brand::create(['name' => 'Katadyn Switzerland', 'website' => 'https://example.test']);
        Brand::create(['name' => 'Oru Designs USA', 'website' => 'https://example.test']);
        Brand::create(['name' => 'Added by hand', 'website' => 'https://example.test']);

        $this->seed(PartnerSeeder::class);

        $names = Brand::pluck('name');

        $this->assertNotContains('Katadyn Switzerland', $names);
        $this->assertNotContains('Oru Designs USA', $names);
        // Anything not created by this seeder is none of its business.
        $this->assertContains('Added by hand', $names);
    }

    public function test_it_can_be_run_twice_without_duplicating_anything(): void
    {
        $this->seed(PartnerSeeder::class);
        $this->seed(PartnerSeeder::class);

        $this->assertCount(count($this->expected), Brand::all());
        // Uploads deduplicate on hash, so no second copy of the one logo.
        $this->assertSame(1, File::count());
        $this->assertSame(1, Image::count());
    }
}
