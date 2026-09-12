<?php

use App\Models\Image;
use App\Models\Recipe;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A step is more than a sentence: it often needs a photograph of what the
 * pan should look like, and an aside that is worth reading but is not part
 * of the instruction.
 *
 * Sources are a separate table because a recipe usually has more than one
 * thing behind it, and each wants its own kind, link and note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_steps', function (Blueprint $table) {
            $table->foreignIdFor(Image::class)
                ->nullable()
                ->after('order')
                ->constrained('images')
                ->nullOnDelete();
            // An aside, not an instruction, so it renders differently.
            $table->text('note')->nullable()->after('body');
        });

        Schema::create('recipe_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Recipe::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->string('kind', 32)->default('found');
            $table->string('label');
            $table->string('url')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_sources');

        Schema::table('recipe_steps', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Image::class);
            $table->dropColumn('note');
        });
    }
};
