<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Support\Ratings;
use Illuminate\Console\Command;

/**
 * Adds every recipe's ratings up again.
 *
 * The published figure is stored on the recipe, so it is the one thing on
 * the site that can drift from the rows behind it - after a hand edit in the
 * database, or a restore, or anything else that writes ratings without going
 * through the app. This puts it back.
 */
class RecountRatings extends Command
{
    protected $signature = 'ratings:recount';

    protected $description = 'Work out every recipe published rating figure again';

    public function handle(): int
    {
        $changed = 0;

        Recipe::query()->withTrashed()->each(function (Recipe $recipe) use (&$changed) {
            $was = [$recipe->rating_count, $recipe->rating_average];

            Ratings::recount($recipe);

            if ($was !== [$recipe->rating_count, $recipe->rating_average]) {
                $changed++;

                $this->line(sprintf(
                    '  %s: %s from %d (was %s from %d)',
                    $recipe->slug,
                    $recipe->rating_average ?? 'nothing',
                    $recipe->rating_count,
                    $was[1] ?? 'nothing',
                    $was[0],
                ));
            }
        });

        $this->info($changed === 0
            ? 'Every recipe already had the right figure.'
            : "Put {$changed} right.");

        return self::SUCCESS;
    }
}
