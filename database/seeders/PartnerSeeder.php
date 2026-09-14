<?php

namespace Database\Seeders;

use App\Enums\ImageType;
use App\Models\Brand;
use App\Services\FileUploadService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Moves the partners that used to be hard-coded in Partners.vue into the
 * database, uploading their bundled logos through the normal pipeline.
 *
 * Idempotent: brands are matched on name, and FileUploadService deduplicates
 * uploads on their hash, so re-running this changes nothing.
 */
class PartnerSeeder extends Seeder
{
    /**
     * The logo is the file name to look for in resources/img/partners. Drop
     * one in under that name and the next run attaches it; until then the
     * brand exists in the admin but stays off the homepage, which only shows
     * brands that have a logo to show.
     *
     * @var array<int, array{name: string, website: string, logo: string}>
     */
    protected array $partners = [
        ['name' => 'Tune Outdoor', 'website' => 'https://tuneoutdoor.com/', 'logo' => 'tune-outdoor.png'],
        ['name' => 'Redarc Electronics', 'website' => 'https://www.redarcelectronics.com/us/', 'logo' => 'redarc.png'],
        ['name' => 'Devos Outdoor', 'website' => 'https://www.devosoutdoor.com/', 'logo' => 'devos-outdoor.png'],
        ['name' => 'Dometic', 'website' => 'https://www.dometic.com/en-us', 'logo' => 'dometic.png'],
        ['name' => 'Midland Radio', 'website' => 'https://midlandusa.com/', 'logo' => 'midland-radio.png'],
        ['name' => 'Baja Designs', 'website' => 'https://bajadesigns.com/', 'logo' => 'baja-designs.png'],
        ['name' => 'Falken Tires', 'website' => 'https://www.falkentire.com/', 'logo' => 'falken-tires.png'],
        ['name' => 'TriPine', 'website' => 'https://tripine.com/', 'logo' => 'tripine.png'],
    ];

    /**
     * Partners this seeder created that are no longer partners.
     *
     * Brands are matched on name and updated in place, so dropping one from
     * the list above leaves the row sitting in the database and on the
     * homepage. Naming it here takes it out on the next run. Only ever add
     * names this seeder itself created; anything added by hand in the admin
     * is not its business.
     *
     * @var array<int, string>
     */
    protected array $retired = [
        'Katadyn Switzerland',
        'Oru Designs USA',
    ];

    public function run(FileUploadService $uploads): void
    {
        $this->retire();

        $waiting = [];

        foreach ($this->partners as $partner) {
            $source = resource_path('img/partners/'.$partner['logo']);
            $logoId = null;

            if (is_file($source)) {
                $logoId = $uploads->store(
                    new UploadedFile($source, $partner['logo'], 'image/png', null, true),
                    type: 'static',
                    name: $partner['name'].' logo',
                    imageType: ImageType::Logo,
                )->image?->id;
            } else {
                $waiting[] = $partner['logo'];
            }

            $brand = Brand::firstOrNew(['name' => $partner['name']]);
            $brand->website = $partner['website'];

            // Never clear a logo somebody attached in the admin just because
            // there is no file for it in the repo.
            if ($logoId !== null) {
                $brand->logo_image_id = $logoId;
            }

            $brand->save();
        }

        $this->command?->info('Seeded '.count($this->partners).' partners.');

        if ($waiting !== []) {
            $this->command?->newLine();
            $this->command?->warn('No logo yet for '.count($waiting).' of them. They are in the admin but stay off');
            $this->command?->warn('the homepage until one is attached. Either upload it there, or drop the file in');
            $this->command?->warn('resources/img/partners and run this again:');
            $this->command?->getOutput()->listing($waiting);
        }
    }

    /**
     * Removes brands this seeder used to create, unless they have since been
     * added back to the list by name.
     */
    protected function retire(): void
    {
        $current = array_column($this->partners, 'name');
        $gone = array_values(array_diff($this->retired, $current));

        if ($gone === []) {
            return;
        }

        $removed = Brand::whereIn('name', $gone)->delete();

        if ($removed > 0) {
            $this->command?->warn('Removed '.$removed.' former '.str('partner')->plural($removed).': '.implode(', ', $gone));
        }
    }
}
