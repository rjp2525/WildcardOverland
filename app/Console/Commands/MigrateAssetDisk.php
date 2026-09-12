<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Moves stored files onto the disk assets are currently served from.
 *
 * Changing config('assets.disk') does not move anything, so every record
 * written under the old setting keeps pointing somewhere the asset route no
 * longer reads. This copies the bytes across and repoints the record.
 */
class MigrateAssetDisk extends Command
{
    protected $signature = 'assets:migrate-disk
                            {--from= : Only move files recorded on this disk}
                            {--pretend : List what would move without moving it}';

    protected $description = 'Copy stored files onto the disk assets are served from';

    public function handle(): int
    {
        $target = config('assets.disk');

        $files = File::query()
            ->where('disk', '!=', $target)
            ->when($this->option('from'), fn ($query, $from) => $query->where('disk', $from))
            ->get();

        if ($files->isEmpty()) {
            $this->info("Every file is already on the [{$target}] disk.");

            return self::SUCCESS;
        }

        $this->line("Moving {$files->count()} ".str('file')->plural($files->count())." onto [{$target}].");

        $moved = 0;
        $failed = [];

        foreach ($files as $file) {
            if ($this->option('pretend')) {
                $this->line("  would move {$file->stored_path} from [{$file->disk}]");

                continue;
            }

            try {
                $bytes = Storage::disk($file->disk)->get($file->stored_path);

                if ($bytes === null) {
                    $failed[] = "{$file->id} ({$file->stored_path}): not on [{$file->disk}]";

                    continue;
                }

                // The path is kept: only the disk it lives on is changing, so
                // there is no reason to invalidate anything rendered from it.
                Storage::disk($target)->put($file->stored_path, $bytes, ['visibility' => 'private']);
                $file->update(['disk' => $target]);

                $moved++;
            } catch (Throwable $e) {
                $failed[] = "{$file->id} ({$file->stored_path}): {$e->getMessage()}";
            }
        }

        if ($this->option('pretend')) {
            return self::SUCCESS;
        }

        $this->info("Moved {$moved}.");

        foreach ($failed as $failure) {
            $this->warn("  could not move {$failure}");
        }

        if ($failed !== []) {
            $this->newLine();
            $this->line('Seeded demo images can be restored with the seeder instead;');
            $this->line('anything uploaded by hand has to be uploaded again.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
