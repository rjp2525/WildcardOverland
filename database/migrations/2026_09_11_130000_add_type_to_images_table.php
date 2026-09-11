<?php

use App\Enums\ImageType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a subject type to images so photographs can be counted separately from
 * logos and other site graphics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('type', 32)
                ->default(ImageType::Photo->value)
                ->index()
                ->after('name');
        });

        // Backfill from a relationship rather than a guess: anything already
        // used as a brand logo is a logo.
        DB::table('images')
            ->whereIn('id', fn ($query) => $query
                ->select('logo_image_id')
                ->from('brands')
                ->whereNotNull('logo_image_id'))
            ->update(['type' => ImageType::Logo->value]);
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
