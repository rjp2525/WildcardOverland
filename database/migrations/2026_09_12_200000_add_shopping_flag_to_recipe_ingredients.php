<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether an ingredient belongs on a shopping list.
 *
 * Camp recipes are full of lines that are not things you buy: whatever is
 * left in the cooler, water, salt you already carry. They belong in the
 * ingredients because you need to know about them, and nowhere near the
 * list you take to a shop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->boolean('in_shopping_list')->default(true)->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropColumn('in_shopping_list');
        });
    }
};
