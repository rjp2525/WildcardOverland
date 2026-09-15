<?php

use App\Models\Recipe;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Folds ratings into reviews.
 *
 * Stars on their own were one tap from anybody, which made them the easiest
 * thing on the site to manufacture and the hardest to stand behind. They are
 * now part of writing a review: a name, an address to reach you at, what you
 * thought and how many stars, all in one go.
 *
 * That collapses the whole thing into one row with one queue and one
 * decision behind it. Nothing counts towards the published figure until the
 * review it came with has been read, so the number in a search result is
 * only ever made of feedback somebody has actually looked at.
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
        Schema::table('recipe_comments', function (Blueprint $table) {
            $table->unsignedTinyInteger('stars')->nullable()->after('name');

            /*
             * Kept so there is a way to reach whoever wrote it, and never
             * sent to the page. One review per address per recipe: coming
             * back to change your mind rewrites the one you left.
             */
            $table->string('email')->nullable()->after('stars');
            $table->unique(['recipe_id', 'email']);
        });

        $this->carryStarsOver();

        Schema::dropIfExists('recipe_ratings');

        $this->recount();
    }

    public function down(): void
    {
        Schema::create('recipe_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Recipe::class)->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stars');
            $table->string('status', 16)->default('counted');
            $table->string('visitor_hash', 64);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['recipe_id', 'visitor_hash']);
            $table->index(['ip_hash', 'created_at']);
            $table->index(['recipe_id', 'status']);
        });

        // Back out of the fold: every review with stars becomes a rating again.
        DB::table('recipe_comments')->whereNotNull('stars')->orderBy('id')->each(
            fn ($comment) => DB::table('recipe_ratings')->insert([
                'recipe_id' => $comment->recipe_id,
                'stars' => $comment->stars,
                'status' => $comment->status === 'approved' ? 'counted' : 'held',
                'visitor_hash' => $comment->visitor_hash,
                'ip_hash' => $comment->ip_hash,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
            ]),
        );

        Schema::table('recipe_comments', function (Blueprint $table) {
            $table->dropUnique(['recipe_id', 'email']);
            $table->dropColumn(['stars', 'email']);
        });
    }

    /**
     * A rating and a review from the same browser were always the same
     * person saying one thing, so they become one row.
     *
     * Stars with no review behind them cannot be carried across, because
     * there is nowhere for them to go and nobody to attribute them to. That
     * is the point of the change rather than a casualty of it.
     */
    protected function carryStarsOver(): void
    {
        if (! Schema::hasTable('recipe_ratings')) {
            return;
        }

        DB::table('recipe_ratings')->orderBy('id')->each(function ($rating) {
            DB::table('recipe_comments')
                ->where('recipe_id', $rating->recipe_id)
                ->where('visitor_hash', $rating->visitor_hash)
                ->whereNull('stars')
                ->update(['stars' => $rating->stars]);
        });
    }

    protected function recount(): void
    {
        DB::table('recipes')->update(['rating_count' => 0, 'rating_average' => null]);

        $totals = DB::table('recipe_comments')
            ->where('status', 'approved')
            ->whereNotNull('stars')
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
