<?php

use App\Models\File;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The images table was created as a stub. The columns below come from the
 * Image model's existing $fillable, which already described the intended
 * shape.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('name')
                ->nullable()
                ->index()
                ->after('id');
            $table->foreignIdFor(File::class, 'file_id')
                ->after('name')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedInteger('width')
                ->nullable()
                ->after('file_id');
            $table->unsignedInteger('height')
                ->nullable()
                ->after('width');
            $table->boolean('private')
                ->default(false)
                ->index()
                ->after('height');
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('file_id');
            $table->dropColumn(['name', 'width', 'height', 'private']);
        });
    }
};
