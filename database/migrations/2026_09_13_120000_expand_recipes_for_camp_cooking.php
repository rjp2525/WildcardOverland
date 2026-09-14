<?php

use App\Models\Recipe;
use App\Models\RecipeIngredientGroup;
use App\Models\RecipeStep;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A real camp cook is not a flat list of ingredients and a flat list of steps.
 *
 * It has parts that are shopped, prepped and cooked separately (the steak,
 * the rice, the sauce), work that happens at home days earlier, and asides
 * that belong to one particular step rather than to the recipe as a whole.
 * Flattening all of that into one list loses the bit that makes it cookable.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ingredients belong to a part: "Steak", "Veggies", "The Sauce".
        Schema::create('recipe_ingredient_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Recipe::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->string('name');
            // The paragraph that follows a part, explaining why it is like that.
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->foreignIdFor(RecipeIngredientGroup::class, 'group_id')
                ->nullable()
                ->after('recipe_id')
                ->constrained('recipe_ingredient_groups')
                ->cascadeOnDelete();

            // Sub-bullets: which cut to buy, what to use instead.
            $table->text('detail')->nullable()->after('note');
            // "Optional but excellent" is a real category out here.
            $table->boolean('optional')->default(false)->after('detail');
        });

        // Prep at home, technique asides, kit lists, scaling up for a crowd.
        Schema::create('recipe_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Recipe::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->string('kind', 32)->default('note');
            // Before or after the method, which is the only choice that
            // changes whether it is read in time to be useful.
            $table->string('placement', 32)->default('after_method');
            $table->string('title');
            $table->text('intro')->nullable();
            $table->longText('body')->nullable();
            $table->timestamps();
        });

        Schema::table('recipe_steps', function (Blueprint $table) {
            // "Sear the steak" reads far better than "Step 2".
            $table->string('title')->nullable()->after('order');
        });

        Schema::table('recipes', function (Blueprint $table) {
            // What to know before the first step, e.g. how the heat zones work.
            $table->text('method_intro')->nullable()->after('notes');
            $table->string('method_title')->nullable()->after('method_intro');
            // "Makes roughly 10 to 12 big servings", which a number cannot say.
            $table->string('yield')->nullable()->after('servings');
        });

        // One step can carry several asides, and they are not all the same
        // sort of thing: some are advice, one of them will ruin dinner.
        Schema::create('recipe_step_tips', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(RecipeStep::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->string('kind', 16)->default('tip');
            $table->string('title')->nullable();
            $table->text('body');
            $table->timestamps();
        });

        $this->moveExistingNotesIntoTips();

        Schema::table('recipe_steps', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }

    /**
     * A step used to have exactly one untitled aside. Those become ordinary
     * tips rather than being thrown away.
     */
    protected function moveExistingNotesIntoTips(): void
    {
        DB::table('recipe_steps')
            ->whereNotNull('note')
            ->where('note', '!=', '')
            ->orderBy('id')
            ->each(function (object $step): void {
                DB::table('recipe_step_tips')->insert([
                    'recipe_step_id' => $step->id,
                    'order' => 0,
                    'kind' => 'tip',
                    'title' => null,
                    'body' => $step->note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('recipe_steps', function (Blueprint $table) {
            $table->text('note')->nullable()->after('body');
        });

        Schema::dropIfExists('recipe_step_tips');

        Schema::table('recipe_steps', function (Blueprint $table) {
            $table->dropColumn('title');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['method_intro', 'method_title', 'yield']);
        });

        Schema::dropIfExists('recipe_sections');

        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn(['detail', 'optional']);
        });

        Schema::dropIfExists('recipe_ingredient_groups');
    }
};
