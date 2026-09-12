<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Reports files whose record and bytes have come apart.
 *
 * A record on its own is enough to build a signed URL, so a missing object
 * does not fail anywhere obvious - it just serves a 404 for an image the
 * site is certain exists. This says which ones, and on which disk.
 */
class CheckAssets extends Command
{
    protected $signature = 'assets:check';

    protected $description = 'Check that every stored file is present on the disk assets are served from';

    public function handle(): int
    {
        $disk = config('assets.disk');
        $this->line("Serving assets from the <options=bold>{$disk}</> disk.");

        try {
            Storage::disk($disk)->exists('.');
        } catch (Throwable $e) {
            $this->error("That disk is not usable: {$e->getMessage()}");

            return self::FAILURE;
        }

        $wrongDisk = [];
        $missing = [];
        $checked = 0;

        File::query()->orderBy('id')->chunk(100, function ($files) use ($disk, &$wrongDisk, &$missing, &$checked): void {
            foreach ($files as $file) {
                $checked++;

                if ($file->disk !== $disk) {
                    $wrongDisk[] = $file;

                    continue;
                }

                if (! Storage::disk($disk)->exists($file->stored_path)) {
                    $missing[] = $file;
                }
            }
        });

        $this->line("Checked {$checked} ".str('file')->plural($checked).'.');

        $this->report('Recorded on another disk', $wrongDisk);
        $this->report('Missing from the disk', $missing);

        if ($wrongDisk === [] && $missing === []) {
            $this->info('Every file is where it should be.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('Seeded demo images repair themselves - re-run the seeder and');
        $this->line('the bytes are written back:');
        $this->line('  <options=bold>php artisan db:seed --class=DemoContentSeeder --force</>');
        $this->line('Anything uploaded by hand has to be uploaded again.');

        return self::FAILURE;
    }

    /**
     * @param  array<int, File>  $files
     */
    protected function report(string $heading, array $files): void
    {
        if ($files === []) {
            return;
        }

        $this->newLine();
        $this->warn($heading.' ('.count($files).'):');

        $this->table(
            ['ID', 'Name', 'Disk', 'Path'],
            array_map(fn (File $file) => [
                $file->id,
                str($file->name)->limit(30)->value(),
                $file->disk,
                str($file->stored_path)->limit(46)->value(),
            ], $files),
        );
    }
}
