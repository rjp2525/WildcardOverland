<?php

use App\Models\Image;
use App\Models\Trip;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trips had no imagery at all. A hero image drives cards and the detail
 * header; the pivot carries an ordered gallery per trip.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignIdFor(Image::class, 'hero_image_id')
                ->nullable()
                ->after('headline')
                ->constrained('images')
                ->nullOnDelete();
        });

        Schema::create('image_trip', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Trip::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Image::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->string('caption')->nullable();
            $table->timestamps();

            $table->unique(['trip_id', 'image_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_trip');

        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hero_image_id');
        });
    }
};
