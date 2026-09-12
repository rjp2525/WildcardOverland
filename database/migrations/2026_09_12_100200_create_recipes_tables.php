<?php

use App\Enums\MealType;
use App\Models\Image;
use App\Models\Recipe;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Camp recipes. Ingredients and steps are child rows rather than prose so
 * they stay ordered and machine-readable - quantities are split out so
 * servings can be scaled later without re-parsing text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('headline')->nullable();
            $table->foreignIdFor(Image::class, 'hero_image_id')
                ->nullable()
                ->constrained('images')
                ->nullOnDelete();
            $table->text('summary')->nullable();
            // Rich text: tips, substitutions, the story behind it.
            $table->longText('notes')->nullable();

            $table->string('meal_type', 32)->default(MealType::Dinner->value)->index();
            $table->string('difficulty', 16)->nullable();
            $table->json('dietary')->nullable();

            $table->unsignedInteger('prep_minutes')->nullable();
            $table->unsignedInteger('cook_minutes')->nullable();
            $table->unsignedInteger('servings')->nullable();

            $table->boolean('is_draft')->default(true)->index();
            $table->dateTime('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Recipe::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            // Kept as a string: "1 1/2" and "a splash" are both valid here.
            $table->string('quantity')->nullable();
            $table->string('unit')->nullable();
            $table->string('item');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('recipe_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Recipe::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_steps');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
    }
};
