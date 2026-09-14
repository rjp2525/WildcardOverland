<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\Trip;
use App\Support\RelatedRecipes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "More recipes" is scored on what a recipe has in common with this one.
 * The weights are a judgement call; what these check is that the ordering
 * follows from them and that nothing ever comes back empty when there is
 * something to show.
 */
class RelatedRecipesTest extends TestCase
{
    use RefreshDatabase;

    protected function recipe(string $name, array $attributes = [], array $items = []): Recipe
    {
        $recipe = Recipe::create([
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'meal_type' => 'dinner',
            'is_draft' => false,
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);

        foreach ($items as $order => $item) {
            $recipe->ingredients()->create(['order' => $order, 'item' => $item]);
        }

        return $recipe;
    }

    public function test_shared_kit_outranks_being_newer(): void
    {
        $subject = $this->recipe('Skottle fried rice', ['cooking_methods' => ['skottle']]);

        $this->recipe('Newest thing', [
            'cooking_methods' => ['jetboil'],
            'meal_type' => 'breakfast',
            'published_at' => now(),
        ]);
        $sameKit = $this->recipe('Skottle smash burgers', [
            'cooking_methods' => ['skottle'],
            'published_at' => now()->subMonth(),
        ]);

        $this->assertSame($sameKit->id, RelatedRecipes::for($subject)->first()->id);
    }

    public function test_shared_ingredients_pull_a_recipe_up(): void
    {
        $subject = $this->recipe('Steak fried rice', [], [
            'sirloin steak', 'jasmine rice', 'soy sauce', 'sesame oil',
        ]);

        $this->recipe('Oatmeal', [], ['rolled oats', 'brown sugar']);
        $overlapping = $this->recipe('Steak and rice bowls', [], [
            'sirloin steak', 'jasmine rice', 'soy sauce',
        ]);

        $this->assertSame($overlapping->id, RelatedRecipes::for($subject)->first()->id);
    }

    public function test_ingredient_matching_ignores_units_and_preparation(): void
    {
        // "2 tbsp toasted sesame oil" and "1 tsp sesame oil" are the same
        // thing, and neither says anything about salt or water.
        $subject = $this->recipe('A', [], ['toasted sesame oil', 'eggs', 'salt', 'water']);

        $onlyNoise = $this->recipe('Only noise', [], ['salt', 'water']);
        $realOverlap = $this->recipe('Real overlap', [], ['sesame oil', 'egg']);

        $order = RelatedRecipes::for($subject)->pluck('id')->all();

        $this->assertSame([$realOverlap->id, $onlyNoise->id], $order);
    }

    public function test_the_same_meal_type_counts(): void
    {
        $subject = $this->recipe('Camp dinner', ['meal_type' => 'dinner']);

        $breakfast = $this->recipe('Camp breakfast', ['meal_type' => 'breakfast', 'published_at' => now()]);
        $dinner = $this->recipe('Another dinner', ['meal_type' => 'dinner', 'published_at' => now()->subYear()]);

        $order = RelatedRecipes::for($subject)->pluck('id')->all();

        $this->assertSame([$dinner->id, $breakfast->id], $order);
    }

    public function test_being_cooked_on_the_same_trip_counts(): void
    {
        $trip = Trip::create(['name' => 'Baja', 'slug' => 'baja']);

        $subject = $this->recipe('Fried rice');
        $subject->trips()->attach($trip);

        $unrelated = $this->recipe('Unrelated', ['published_at' => now()]);
        $sameTrip = $this->recipe('Cooked on the same trip', ['published_at' => now()->subYear()]);
        $sameTrip->trips()->attach($trip);

        $this->assertSame($sameTrip->id, RelatedRecipes::for($subject)->first()->id);
        $this->assertContains($unrelated->id, RelatedRecipes::for($subject)->pluck('id'));
    }

    public function test_nothing_in_common_still_returns_something(): void
    {
        $subject = $this->recipe('Alone', ['meal_type' => 'dinner'], ['quinoa']);
        $other = $this->recipe('Totally different', ['meal_type' => 'breakfast'], ['oats']);

        // Scored, not filtered: an empty list is worse than a weak suggestion.
        $this->assertSame([$other->id], RelatedRecipes::for($subject)->pluck('id')->all());
    }

    public function test_it_never_suggests_the_recipe_you_are_reading(): void
    {
        $subject = $this->recipe('Fried rice', ['cooking_methods' => ['skottle']], ['rice']);
        $this->recipe('Another', ['cooking_methods' => ['skottle']], ['rice']);

        $this->assertNotContains($subject->id, RelatedRecipes::for($subject)->pluck('id'));
    }

    public function test_drafts_are_never_suggested(): void
    {
        $subject = $this->recipe('Fried rice', ['cooking_methods' => ['skottle']]);
        $this->recipe('Draft', ['cooking_methods' => ['skottle'], 'is_draft' => true]);

        $this->assertCount(0, RelatedRecipes::for($subject));
    }

    public function test_it_stops_at_three_by_default(): void
    {
        $subject = $this->recipe('Fried rice');

        foreach (range(1, 6) as $n) {
            $this->recipe("Other {$n}");
        }

        $this->assertCount(3, RelatedRecipes::for($subject));
        $this->assertCount(5, RelatedRecipes::for($subject, 5));
    }

    public function test_the_page_shows_the_related_ones(): void
    {
        $subject = $this->recipe('Skottle fried rice', ['cooking_methods' => ['skottle']]);
        $this->recipe('Jetboil porridge', ['cooking_methods' => ['jetboil'], 'meal_type' => 'breakfast']);
        $related = $this->recipe('Skottle smash burgers', ['cooking_methods' => ['skottle']]);

        $this->get(route('recipes.show', $subject->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('more', 2)
                ->where('more.0.slug', $related->slug));
    }
}
