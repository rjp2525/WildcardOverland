<?php

namespace Tests\Feature\Admin;

use App\Enums\DietaryTag;
use App\Enums\Difficulty;
use App\Enums\MealType;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return [
            'name' => 'Camp Breakfast Hash',
            'meal_type' => MealType::Breakfast->value,
            'difficulty' => Difficulty::Easy->value,
            'prep_minutes' => 10,
            'cook_minutes' => 20,
            'servings' => 2,
            'dietary' => [DietaryTag::OnePot->value],
            'ingredients' => [
                ['quantity' => '3', 'item' => 'potatoes', 'note' => 'diced'],
                ['item' => 'salt', 'note' => 'to taste'],
            ],
            'steps' => [
                ['body' => 'Fry the potatoes.'],
                ['body' => 'Add the eggs.'],
            ],
            ...$overrides,
        ];
    }

    public function test_a_recipe_is_created_with_its_ingredients_and_steps(): void
    {
        $this->post(route('admin.recipes.store'), $this->payload())->assertRedirect();

        $recipe = Recipe::sole();

        $this->assertSame('camp-breakfast-hash', $recipe->slug);
        $this->assertSame(MealType::Breakfast, $recipe->meal_type);
        $this->assertSame(Difficulty::Easy, $recipe->difficulty);
        $this->assertSame(30, $recipe->totalMinutes());
        $this->assertSame(['potatoes', 'salt'], $recipe->ingredients->pluck('item')->all());
        // Order is assigned from the submitted sequence.
        $this->assertSame([0, 1], $recipe->steps->pluck('order')->all());
        $this->assertSame('3 potatoes', $recipe->ingredients->first()->label());
    }

    public function test_dietary_tags_round_trip(): void
    {
        $this->post(route('admin.recipes.store'), $this->payload([
            'dietary' => [DietaryTag::Vegan->value, DietaryTag::NoCook->value],
        ]))->assertRedirect();

        $this->assertEqualsCanonicalizing(
            ['vegan', 'no-cook'],
            Recipe::sole()->dietary,
        );
    }

    public function test_validation_rejects_bad_enums_and_empty_child_rows(): void
    {
        $this->post(route('admin.recipes.store'), $this->payload([
            'name' => '',
            'meal_type' => 'elevenses',
            'difficulty' => 'impossible',
            'dietary' => ['carnivore'],
            'ingredients' => [['item' => '']],
            'steps' => [['body' => '']],
        ]))->assertSessionHasErrors([
            'name',
            'meal_type',
            'difficulty',
            'dietary.0',
            'ingredients.0.item',
            'steps.0.body',
        ]);

        $this->assertSame(0, Recipe::count());
    }

    public function test_updating_replaces_ingredients_and_steps(): void
    {
        $this->post(route('admin.recipes.store'), $this->payload());
        $recipe = Recipe::sole();

        $this->put(route('admin.recipes.update', $recipe), $this->payload([
            'slug' => $recipe->slug,
            'ingredients' => [['item' => 'oats']],
            'steps' => [['body' => 'Boil water.']],
        ]))->assertRedirect();

        $this->assertSame(['oats'], $recipe->fresh()->ingredients->pluck('item')->all());
        $this->assertSame(1, $recipe->fresh()->steps->count());
    }

    public function test_deleting_a_recipe_cascades_to_its_children(): void
    {
        $this->post(route('admin.recipes.store'), $this->payload());
        $recipe = Recipe::sole();

        $this->delete(route('admin.recipes.destroy', $recipe))->assertRedirect();

        // Soft deleted, so the children are kept for a restore.
        $this->assertSoftDeleted($recipe);
        $this->assertDatabaseCount('recipe_ingredients', 2);
    }

    public function test_slugs_are_unique_across_recipes(): void
    {
        $this->post(route('admin.recipes.store'), $this->payload());
        $this->post(route('admin.recipes.store'), $this->payload())
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Recipe::count());
    }
}
