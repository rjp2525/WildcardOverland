<?php

use App\Models\Trip;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The campsites table was created as a stub and the model carried no
 * definition. Campsites are modelled as stops belonging to a trip, ordered
 * within it and managed from the trip's edit screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campsites', function (Blueprint $table) {
            $table->foreignIdFor(Trip::class, 'trip_id')
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedInteger('order')
                ->default(0)
                ->after('trip_id');
            $table->string('name')
                ->after('order');
            $table->decimal('latitude', 10, 7)
                ->nullable()
                ->after('name');
            $table->decimal('longitude', 10, 7)
                ->nullable()
                ->after('latitude');
            $table->unsignedInteger('nights')
                ->nullable()
                ->after('longitude');
            $table->text('notes')
                ->nullable()
                ->after('nights');
        });
    }

    public function down(): void
    {
        Schema::table('campsites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trip_id');
            $table->dropColumn([
                'order',
                'name',
                'latitude',
                'longitude',
                'nights',
                'notes',
            ]);
        });
    }
};
