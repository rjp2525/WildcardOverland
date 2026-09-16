<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Statuses are written out as strings on purpose. A migration has to keep
 * working for the life of the repository, and an enum it imports can be
 * renamed or deleted by a later change.
 */

/**
 * An address has to be answered before the review behind it goes anywhere.
 *
 * Asking for an address raised the cost of inventing a review. Answering one
 * is what makes it real: until somebody follows the link sent to it, the
 * review is not in the queue, not on the page and not in the average, and
 * whoever typed it has proved nothing except that they can type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_comments', function (Blueprint $table) {
            $table->dateTime('confirmed_at')->nullable()->after('email');
            $table->index(['recipe_id', 'confirmed_at']);
        });

        /*
         * Everything already sent in predates the link, and a review that is
         * on the page must not fall off it because of a column that did not
         * exist when it was written.
         */
        DB::table('recipe_comments')->update(['confirmed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('recipe_comments', function (Blueprint $table) {
            $table->dropIndex(['recipe_id', 'confirmed_at']);
            $table->dropColumn('confirmed_at');
        });
    }
};
