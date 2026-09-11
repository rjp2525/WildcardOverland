<?php

use App\Models\Image;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The brands table was created as a stub. The columns below come from the
 * Brand model's existing $fillable, which already described the intended
 * shape.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('name')
                ->after('id');
            $table->foreignIdFor(Image::class, 'logo_image_id')
                ->nullable()
                ->after('name')
                ->constrained('images')
                ->nullOnDelete();
            $table->string('website')
                ->nullable()
                ->after('logo_image_id');
            $table->text('description')
                ->nullable()
                ->after('website');
            $table->string('primary_color', 32)
                ->nullable()
                ->after('description');
            $table->string('secondary_color', 32)
                ->nullable()
                ->after('primary_color');
            $table->text('notes')
                ->nullable()
                ->after('secondary_color');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropConstrainedForeignId('logo_image_id');
            $table->dropColumn([
                'name',
                'website',
                'description',
                'primary_color',
                'secondary_color',
                'notes',
            ]);
        });
    }
};
