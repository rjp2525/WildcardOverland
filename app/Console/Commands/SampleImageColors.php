<?php

namespace App\Console\Commands;

use App\Models\Image as ImageModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Fills in the placeholder colour for images that were stored before it was
 * being recorded. New uploads sample it themselves; this is only for the
 * back catalogue, and is safe to run repeatedly.
 */
class SampleImageColors extends Command
{
    protected $signature = 'assets:sample-colors {--all : Re-sample images that already have a colour}';

    protected $description = 'Sample the placeholder colour for stored images';

    public function handle(): int
    {
        $disk = config('assets.disk');

        $images = ImageModel::query()
            ->with('file')
            ->whereHas('file')
            ->unless($this->option('all'), fn ($query) => $query->whereNull('dominant_color'))
            ->get();

        if ($images->isEmpty()) {
            $this->info('Nothing to sample.');

            return self::SUCCESS;
        }

        $sampled = 0;
        $skipped = 0;

        $this->withProgressBar($images, function (ImageModel $image) use ($disk, &$sampled, &$skipped): void {
            try {
                if (! Storage::disk($disk)->exists($image->file->stored_path)) {
                    $skipped++;

                    return;
                }

                $image->update([
                    'dominant_color' => Image::fromStorage($image->file->stored_path, $disk)->dominantColor(),
                ]);

                $sampled++;
            } catch (Throwable $e) {
                $skipped++;
            }
        });

        $this->newLine(2);
        $this->info("Sampled {$sampled}.".($skipped > 0 ? " Skipped {$skipped} that could not be read." : ''));

        return self::SUCCESS;
    }
}
