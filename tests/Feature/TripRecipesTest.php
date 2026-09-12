<?php

namespace Tests\Feature;

use App\Enums\CookingMethod;
use App\Enums\MealType;
use App\Models\Recipe;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripRecipesTest extends TestCase
{
    use RefreshDatabase;

    protected function trip(): Trip
    {
        return Trip::create([
            'name' => 'Mojave Road', 'slug' => 'mojave-road',
            'is_draft' => false, 'published_at' => now()->subDay(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function recipe(string $name, array $attributes = []): Recipe
    {
        return Recipe::create([
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'meal_type' => MealType::Dinner,
            'is_draft' => false,
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    public function test_a_trip_shows_the_recipes_cooked_on_it_in_order(): void
    {
        $trip = $this->trip();
        $chili = $this->recipe('Dutch Oven Chili');
        $bread = $this->recipe('Skillet Cornbread');

        $trip->recipes()->attach([
            $bread->id => ['order' => 1],
            $chili->id => ['order' => 0],
        ]);

        $this->get(route('trips.show', $trip->slug))
            ->assertInertia(fn ($page) => $page
                ->has('trip.recipes', 2)
                ->where('trip.recipes.0.name', 'Dutch Oven Chili')
                ->where('trip.recipes.1.name', 'Skillet Cornbread'));
    }

    public function test_a_trip_with_no_recipes_reports_an_empty_list(): void
    {
        $this->get(route('trips.show', $this->trip()->slug))
            ->assertInertia(fn ($page) => $page->has('trip.recipes', 0));
    }

    public function test_a_draft_recipe_is_not_shown_on_a_trip(): void
    {
        $trip = $this->trip();
        $trip->recipes()->attach($this->recipe('Live one')->id, ['order' => 0]);
        $trip->recipes()->attach($this->recipe('Still cooking', ['is_draft' => true])->id, ['order' => 1]);

        $this->get(route('trips.show', $trip->slug))
            ->assertInertia(fn ($page) => $page
                ->has('trip.recipes', 1)
                ->where('trip.recipes.0.name', 'Live one'));
    }

    public function test_a_recipe_reports_what_it_is_cooked_on(): void
    {
        $recipe = $this->recipe('Dutch Oven Chili', [
            'cooking_methods' => [
                CookingMethod::DutchOven->value,
                CookingMethod::Campfire->value,
            ],
        ]);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->has('recipe.cooked_on', 2)
                ->where('recipe.cooked_on.0.value', 'dutch-oven')
                ->where('recipe.cooked_on.0.label', 'Dutch oven')
                ->where('recipe.cooked_on.1.label', 'Campfire'));
    }

    public function test_an_unknown_cooking_method_is_dropped_rather_than_rendered(): void
    {
        $recipe = $this->recipe('Mystery', ['cooking_methods' => ['dutch-oven', 'microwave']]);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertInertia(fn ($page) => $page->has('recipe.cooked_on', 1));
    }

    public function test_a_recipe_with_no_kit_listed_says_nothing(): void
    {
        $this->get(route('recipes.show', $this->recipe('Cold oats')->slug))
            ->assertInertia(fn ($page) => $page->has('recipe.cooked_on', 0));
    }

    public function test_ingredients_carry_whether_they_belong_on_a_shopping_list(): void
    {
        $recipe = $this->recipe('Camp Breakfast Hash');
        $recipe->ingredients()->create(['order' => 0, 'quantity' => '3', 'item' => 'potatoes']);
        $recipe->ingredients()->create([
            'order' => 1,
            'item' => 'whatever else is in the cooler',
            'in_shopping_list' => false,
        ]);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->has('recipe.ingredients', 2)
                ->where('recipe.ingredients.0.shopping', true)
                ->where('recipe.ingredients.1.shopping', false));
    }

    public function test_an_ingredient_is_on_the_shopping_list_unless_it_is_taken_off(): void
    {
        $recipe = $this->recipe('Chili');
        $recipe->ingredients()->create(['order' => 0, 'item' => 'beans']);

        $this->assertTrue($recipe->ingredients()->first()->in_shopping_list);
    }

    public function test_a_recipe_can_belong_to_several_trips(): void
    {
        $chili = $this->recipe('Dutch Oven Chili');

        $first = $this->trip();
        $second = Trip::create([
            'name' => 'Alpine Loop', 'slug' => 'alpine-loop',
            'is_draft' => false, 'published_at' => now()->subDay(),
        ]);

        $first->recipes()->attach($chili->id, ['order' => 0]);
        $second->recipes()->attach($chili->id, ['order' => 0]);

        $this->assertSame(2, $chili->trips()->count());
    }
}
