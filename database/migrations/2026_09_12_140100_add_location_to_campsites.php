<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Region and country resolved from a campsite's coordinates, cached here so
 * the "states" counter never geocodes at request time. Populated by
 * ReverseGeocodeCampsite; `geocoded_at` distinguishes "not looked up yet"
 * from "looked up and the service had no answer".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campsites', function (Blueprint $table) {
            $table->string('state')
                ->nullable()
                ->index()
                ->after('longitude');
            $table->string('country_code', 2)
                ->nullable()
                ->after('state');
            $table->timestamp('geocoded_at')
                ->nullable()
                ->after('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('campsites', function (Blueprint $table) {
            $table->dropColumn(['state', 'country_code', 'geocoded_at']);
        });
    }
};
