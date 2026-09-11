<?php

namespace Database\Seeders;

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
            'name' => 'Katadyn Switzerland',
            'website' => 'https://www.katadyngroup.com/us/en/brands/Katadyn~b4906/overview',
            'logo' => 'katadyn.png',
        ],
        [
            'name' => 'Oru Designs USA',
            'website' => 'https://www.orudesignsusa.com/',
            'logo' => 'oru.png',
        ],
        [
            'name' => 'TriPine',
            'website' => 'https://tripine.com/',
            'logo' => 'tripine.png',
        ],
    ];

    public function run(FileUploadService $uploads): void
    {
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
}
