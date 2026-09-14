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
     * @var array<int, array{name: string, website: string, logo: string}>
     */
    protected array $partners = [
        [
            'name' => 'TriPine',
            'website' => 'https://tripine.com/',
            'logo' => 'tripine.png',
        ],
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

        foreach ($this->partners as $partner) {
            $source = resource_path('img/partners/'.$partner['logo']);

            if (! is_file($source)) {
                $this->command?->warn("Missing logo for {$partner['name']}: {$source}");

                continue;
            }

            $file = $uploads->store(
                new UploadedFile($source, $partner['logo'], 'image/png', null, true),
                type: 'static',
                name: $partner['name'].' logo',
                imageType: ImageType::Logo,
            );

            Brand::updateOrCreate(
                ['name' => $partner['name']],
                [
                    'website' => $partner['website'],
                    'logo_image_id' => $file->image?->id,
                ],
            );

            $this->command?->info("Seeded partner {$partner['name']}");
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
