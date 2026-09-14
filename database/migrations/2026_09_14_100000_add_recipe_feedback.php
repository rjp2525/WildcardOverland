<?php

use App\Models\Image;
use App\Models\Recipe;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ratings, comments and photographs from people who cooked the thing.
 *
 * Nobody signs up, so there is no user to hang any of this off. A visitor is
 * a random token in their own cookie, hashed before it is stored: enough to
 * recognise the same person coming back to change their mind, not enough to
 * be a record of anybody.
 *
 * The address is hashed rather than kept, because the only thing it is for
 * is noticing a hundred submissions from one place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Recipe::class)->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stars');
            $table->string('visitor_hash', 64);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            // One rating per person per recipe. Changing your mind updates it.
            $table->unique(['recipe_id', 'visitor_hash']);
            $table->index(['ip_hash', 'created_at']);
        });

        Schema::create('recipe_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Recipe::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('body');

            // One photograph per comment, once somebody has looked at it.
            $table->foreignIdFor(Image::class)
                ->nullable()
                ->constrained('images')
                ->nullOnDelete();

            /*
             * Nothing from a stranger goes on the page before it has been
             * read. A personal site with an open comment box and no queue is
             * a spam host by the end of the week.
             */
            $table->string('status', 16)->default('pending');
            $table->dateTime('approved_at')->nullable();

            $table->string('visitor_hash', 64);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['recipe_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['ip_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_comments');
        Schema::dropIfExists('recipe_ratings');
    }
};
