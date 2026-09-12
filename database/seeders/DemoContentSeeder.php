<?php

namespace Database\Seeders;

use App\Enums\ImageType;
use App\Models\Campsite;
use App\Models\File;
use App\Models\Image;
use App\Models\Recipe;
use App\Models\Trip;
use App\Models\VehicleModification;
use App\Services\FileUploadService;
use Database\Seeders\Demo\DemoContent;
use Database\Seeders\Demo\DemoImageFactory;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Populates the site with placeholder content so it does not look empty
 * before real trips and recipes exist.
 *
 * Everything it creates is defined in DemoContent and removable with:
 *
 *     php artisan db:seed --class=DemoContentCleanupSeeder
 *
 * Idempotent: records match on slug and images deduplicate on their hash, so
 * running it twice changes nothing.
 */
class DemoContentSeeder extends Seeder
{
    public function run(FileUploadService $uploads): void
    {
        $images = $this->seedImages($uploads);

        $this->seedTrips($images);
        $this->seedRecipes($images);
        $this->linkRecipesToTrips();
        $this->seedModifications();
        $this->seedGallery($images);

        $this->command?->newLine();
        $this->command?->info('Demo content seeded. Remove it with:');
        $this->command?->line('  php artisan db:seed --class=DemoContentCleanupSeeder');
    }

    /**
     * @return array<string, int> image key => Image id
     */
    protected function seedImages(FileUploadService $uploads): array
    {
        $factory = new DemoImageFactory;
        $ids = [];

        foreach (DemoContent::imageKeys() as $key) {
            $path = tempnam(sys_get_temp_dir(), 'demo').'.png';
            file_put_contents($path, $factory->png($key));

            $file = $uploads->store(
                new UploadedFile($path, "{$key}.png", 'image/png', null, true),
                type: 'content',
                name: $this->titleFor($key),
                imageType: ImageType::Photo,
            );

            @unlink($path);

            $ids[$key] = $file->image?->id;
        }

        $this->command?->info('Generated '.count($ids).' placeholder images.');

        return array_filter($ids);
    }

    /**
     * @param  array<string, int>  $images
     */
    protected function seedTrips(array $images): void
    {
        foreach (DemoContent::trips() as $data) {
            DB::transaction(function () use ($data, $images): void {
                $trip = Trip::updateOrCreate(
                    ['slug' => $data['slug']],
                    [
                        'name' => $data['name'],
                        'headline' => $data['headline'],
                        'summary' => $data['summary'],
                        'content' => $data['content'],
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'miles' => $data['miles'],
                        'hero_image_id' => $images[$data['image']] ?? null,
                        'is_draft' => false,
                        'published_at' => $data['end_date'],
                    ],
                );

                $trip->campsites()->delete();

                foreach (array_values($data['campsites']) as $order => $camp) {
                    // State is set directly: a seeder must not need the network.
                    Campsite::withoutEvents(fn () => $trip->campsites()->create([
                        'order' => $order,
                        'name' => $camp['name'],
                        'latitude' => $camp['lat'],
                        'longitude' => $camp['lng'],
                        'state' => $camp['state'],
                        'country_code' => $camp['country'],
                        'geocoded_at' => now(),
                        'nights' => $camp['nights'] ?? null,
                        'notes' => $camp['notes'] ?? null,
                    ]));
                }

                if (isset($images[$data['image']])) {
                    $trip->images()->sync([
                        $images[$data['image']] => ['order' => 0, 'caption' => $data['headline']],
                    ]);
                }
            });
        }

        $this->command?->info('Seeded '.count(DemoContent::trips()).' trips.');
    }

    /**
     * @param  array<string, int>  $images
     */
    protected function seedRecipes(array $images): void
    {
        foreach (DemoContent::recipes() as $data) {
            DB::transaction(function () use ($data, $images): void {
                $recipe = Recipe::updateOrCreate(
                    ['slug' => $data['slug']],
                    [
                        'name' => $data['name'],
                        'headline' => $data['headline'],
                        'summary' => $data['summary'],
                        'notes' => $data['notes'],
                        'meal_type' => $data['meal_type'],
                        'difficulty' => $data['difficulty'],
                        'dietary' => $data['dietary'],
                        'prep_minutes' => $data['prep'],
                        'cook_minutes' => $data['cook'],
                        'servings' => $data['servings'],
                        'hero_image_id' => $images[$data['image']] ?? null,
                        'is_draft' => false,
                        'published_at' => now()->subDays(random_int(3, 200)),
                    ],
                );

                $recipe->ingredients()->delete();
                foreach (array_values($data['ingredients']) as $order => [$qty, $unit, $item, $note]) {
                    $recipe->ingredients()->create([
                        'order' => $order,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'item' => $item,
                        'note' => $note,
                    ]);
                }

                $recipe->steps()->delete();
                foreach (array_values($data['steps']) as $order => $body) {
                    $recipe->steps()->create(['order' => $order, 'body' => $body]);
                }
            });
        }

        $this->command?->info('Seeded '.count(DemoContent::recipes()).' recipes.');
    }

    /**
     * Recipes are seeded after trips, so the pairing happens once both exist.
     * Matching on name keeps DemoContent readable; slugs live in the seeder.
     */
    protected function linkRecipesToTrips(): void
    {
        $recipes = Recipe::pluck('id', 'name');

        foreach (DemoContent::trips() as $data) {
            $trip = Trip::where('name', $data['name'])->first();

            if ($trip === null) {
                continue;
            }

            $trip->recipes()->sync(
                collect($data['recipes'] ?? [])
                    ->map(fn (string $name) => $recipes[$name] ?? null)
                    ->filter()
                    ->values()
                    ->mapWithKeys(fn (int $id, int $order) => [$id => ['order' => $order]])
                    ->all(),
            );
        }
    }

    protected function seedModifications(): void
    {
        foreach (DemoContent::modifications() as $data) {
            VehicleModification::updateOrCreate(
                ['name' => $data['name']],
                [
                    'vendor' => $data['vendor'],
                    'description' => $data['description'],
                    'install_date' => $data['install_date'],
                    'cost' => $data['cost'],
                    'url' => $data['url'] ?? null,
                    'build_layer' => $data['layer'] ?? null,
                    'hotspot_x' => $data['hotspot'][0] ?? null,
                    'hotspot_y' => $data['hotspot'][1] ?? null,
                    'shown_on_timeline' => $data['timeline'],
                ],
            );
        }

        $this->command?->info('Seeded '.count(DemoContent::modifications()).' modifications.');
    }

    /**
     * @param  array<string, int>  $images
     */
    protected function seedGallery(array $images): void
    {
        foreach (array_values(DemoContent::galleryImages()) as $order => $entry) {
            if (! isset($images[$entry['key']])) {
                continue;
            }

            Image::whereKey($images[$entry['key']])->update([
                'featured' => true,
                'sort_order' => $order,
                'caption' => $entry['caption'],
            ]);
        }

        $this->command?->info('Featured '.count(DemoContent::galleryImages()).' gallery images.');
    }

    protected function titleFor(string $key): string
    {
        return ucwords(str_replace('-', ' ', $key));
    }

    /**
     * The files this seeder creates, found by the hash of their deterministic
     * bytes rather than by name - so cleanup cannot catch anything else.
     *
     * @return Collection<int, File>
     */
    public static function seededFiles(): Collection
    {
        $factory = new DemoImageFactory;

        $hashes = array_map(
            fn (string $key) => hash('sha256', $factory->png($key)),
            DemoContent::imageKeys(),
        );

        return File::whereIn('hash', $hashes)->get();
    }
}
