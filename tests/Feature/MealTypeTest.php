<?php

namespace Tests\Feature;

use App\Enums\MealType;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Meal types are a fixed list in code, so adding one has to reach every
 * place that offers or filters by them without anyone remembering to go
 * and update a table.
 */
class MealTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_side_dish_reads_as_two_words(): void
    {
        // The value is a slug, so the default ucfirst would say "Side-dish".
        $this->assertSame('Side dish', MealType::SideDish->label());
        $this->assertSame('side-dish', MealType::SideDish->value);
    }

    public function test_it_is_offered_on_the_admin_form(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.recipes.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'mealTypes',
                fn ($types) => collect($types)->contains(
                    fn ($type) => $type['value'] === 'side-dish' && $type['label'] === 'Side dish',
                ),
            ));
    }

    public function test_a_recipe_can_be_saved_as_one(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.recipes.store'), [
            'name' => 'Skillet cornbread',
            'meal_type' => MealType::SideDish->value,
        ])->assertRedirect();

        $this->assertSame(MealType::SideDish, Recipe::sole()->meal_type);
    }

    public function test_the_public_listing_can_be_filtered_to_it(): void
    {
        Recipe::create([
            'name' => 'Skillet cornbread', 'slug' => 'skillet-cornbread',
            'meal_type' => MealType::SideDish, 'is_draft' => false, 'published_at' => now()->subDay(),
        ]);
        Recipe::create([
            'name' => 'Fried rice', 'slug' => 'fried-rice',
            'meal_type' => MealType::Dinner, 'is_draft' => false, 'published_at' => now()->subDay(),
        ]);

        $this->get(route('recipes.index', ['meal' => 'side-dish']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('recipes.data', 1)
                ->where('recipes.data.0.name', 'Skillet cornbread')
                ->where('activeMeal', 'side-dish'));
    }

    public function test_it_reaches_the_recipe_page_and_its_structured_data(): void
    {
        $recipe = Recipe::create([
            'name' => 'Skillet cornbread', 'slug' => 'skillet-cornbread',
            'meal_type' => MealType::SideDish, 'is_draft' => false, 'published_at' => now()->subDay(),
        ]);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('recipe.meal_type', 'Side dish'))
            // recipeCategory, and the Open Graph section with it.
            ->assertSee('"recipeCategory":"Side dish"', escape: false)
            ->assertSee('<meta property="article:section" content="Side dish">', escape: false);
    }

    public function test_existing_recipes_are_untouched_by_the_new_type(): void
    {
        // Nothing to backfill: a recipe stores its own value, and adding a
        // case to the list does not reach back into rows already written.
        $recipe = Recipe::create([
            'name' => 'Fried rice', 'slug' => 'fried-rice', 'meal_type' => MealType::Dinner,
        ]);

        $this->assertSame(MealType::Dinner, $recipe->fresh()->meal_type);
    }
}
