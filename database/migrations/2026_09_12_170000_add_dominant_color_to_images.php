<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The average colour of an image, sampled once at upload.
 *
 * It is what the browser paints while the real bytes are still arriving, so
 * a page loads into roughly the right colours instead of into grey holes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('dominant_color', 7)->nullable()->after('height');
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn('dominant_color');
        });
    }
};
