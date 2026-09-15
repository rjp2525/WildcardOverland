<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits what a recipe collects from what it publishes.
 *
 * A cookie is the only thing telling one anonymous person from another, and
 * a cookie can be thrown away. So the raw rows are no longer the rating:
 * each row carries whether it counts, and the figure the page shows, the
 * card shows and the structured data claims is stored on the recipe itself.
 *
 * One number in one place, because a page that displays an average its
 * markup does not match is the other way to lose a rich result.
 */
/*
 * Statuses are written out as strings on purpose. A migration has to keep
 * working for the life of the repository, and an enum it imports can be
 * renamed or deleted by a later change - which is exactly what happened to
 * the one this file used to reference.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_ratings', function (Blueprint $table) {
            $table->string('status', 16)->default('counted')->after('stars');
            $table->index(['recipe_id', 'status']);
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->unsignedInteger('rating_count')->default(0)->after('published_at');
            $table->decimal('rating_average', 3, 2)->nullable()->after('rating_count');
        });

        $this->holdDuplicateAddresses();
        $this->recount();
    }

    public function down(): void
    {
        Schema::table('recipe_ratings', function (Blueprint $table) {
            $table->dropIndex(['recipe_id', 'status']);
            $table->dropColumn('status');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['rating_count', 'rating_average']);
        });
    }

    /**
     * Existing rows get the same rule as new ones rather than a free pass:
     * the first rating from an address counts, anything after it waits to be
     * looked at.
     */
    protected function holdDuplicateAddresses(): void
    {
        $seen = [];

        DB::table('recipe_ratings')
            ->whereNotNull('ip_hash')
            ->orderBy('id')
            ->each(function ($rating) use (&$seen) {
                $key = $rating->recipe_id.'|'.$rating->ip_hash;

                if (! isset($seen[$key])) {
                    $seen[$key] = true;

                    return;
                }

                DB::table('recipe_ratings')
                    ->where('id', $rating->id)
                    ->update(['status' => 'held']);
            });
    }

    protected function recount(): void
    {
        $totals = DB::table('recipe_ratings')
            ->where('status', 'counted')
            ->groupBy('recipe_id')
            ->selectRaw('recipe_id, count(*) as total, avg(stars) as average')
            ->get();

        foreach ($totals as $total) {
            DB::table('recipes')->where('id', $total->recipe_id)->update([
                'rating_count' => $total->total,
                'rating_average' => round((float) $total->average, 2),
            ]);
        }
    }
};
