<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields for the interactive build page: where a part sits on the truck
 * illustration, which parallax layer it belongs to, and an affiliate link
 * kept separate from the plain product URL so one can exist without the other.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_modifications', function (Blueprint $table) {
            $table->string('affiliate_url')->nullable()->after('url');
            // Percentages across the illustration, so the layout stays
            // resolution independent.
            $table->decimal('hotspot_x', 5, 2)->nullable()->after('affiliate_url');
            $table->decimal('hotspot_y', 5, 2)->nullable()->after('hotspot_x');
            $table->string('build_layer', 32)->nullable()->index()->after('hotspot_y');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_modifications', function (Blueprint $table) {
            $table->dropColumn(['affiliate_url', 'hotspot_x', 'hotspot_y', 'build_layer']);
        });
    }
};
