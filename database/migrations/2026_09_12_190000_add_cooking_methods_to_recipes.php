<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a recipe is cooked on. A list rather than one value, because most
 * camp meals use more than one thing, and stored the same way as the
 * dietary tags next to it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->json('cooking_methods')->nullable()->after('dietary');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn('cooking_methods');
        });
    }
};
