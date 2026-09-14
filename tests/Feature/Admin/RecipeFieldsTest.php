<?php

namespace Tests\Feature\Admin;

use App\Enums\CookingMethod;
use App\Enums\Difficulty;
use App\Enums\MealType;
use App\Models\Recipe;
use App\Models\User;
use App\Support\RichText\TipTap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every field on the recipe form, through create and through update.
 *
 * Creating and editing take different routes into the model, and a field
 * that only survives one of them looks like it works right up until someone
 * fills the form in once and loses half of it.
 */
class RecipeFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return [
            'name' => 'Skottle steak fried rice',
            'slug' => 'skottle-steak-fried-rice',
            'headline' => 'Big, crispy, ridiculous',
            'summary' => 'Day-old rice, seared steak and a very hot Skottle.',
            'notes' => TipTap::fromText('Better the next day.'),
            'method_title' => 'Cooking it on the Skottle',
            'method_intro' => TipTap::fromText('Dead centre is screaming hot.'),
            'meal_type' => MealType::Dinner->value,
            'difficulty' => Difficulty::Easy->value,
            'dietary' => ['one-pot', 'make-ahead'],
            'cooking_methods' => [CookingMethod::Skottle->value, CookingMethod::Campfire->value],
            'prep_minutes' => 25,
            'cook_minutes' => 30,
            'servings' => 12,
            'yield' => '10 to 12 big servings',
            'is_draft' => false,
            'published_at' => '2026-09-01T10:00',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $sent
     */
    protected function assertEverythingLanded(Recipe $recipe, array $sent): void
    {
        $this->assertSame($sent['name'], $recipe->name, 'name');
        $this->assertSame($sent['slug'], $recipe->slug, 'slug');
        $this->assertSame($sent['headline'], $recipe->headline, 'headline');
        $this->assertSame($sent['summary'], $recipe->summary, 'summary');
        $this->assertSame('<p>Better the next day.</p>', TipTap::html($recipe->notes), 'notes');
        $this->assertSame($sent['method_title'], $recipe->method_title, 'method_title');
        $this->assertSame(
            '<p>Dead centre is screaming hot.</p>',
            TipTap::html($recipe->method_intro),
            'method_intro',
        );
        $this->assertSame($sent['meal_type'], $recipe->meal_type->value, 'meal_type');
        $this->assertSame($sent['difficulty'], $recipe->difficulty?->value, 'difficulty');
        $this->assertSame($sent['dietary'], $recipe->dietary, 'dietary');
        $this->assertSame($sent['cooking_methods'], $recipe->cooking_methods, 'cooking_methods');
        $this->assertSame($sent['prep_minutes'], $recipe->prep_minutes, 'prep_minutes');
        $this->assertSame($sent['cook_minutes'], $recipe->cook_minutes, 'cook_minutes');
        $this->assertSame($sent['servings'], $recipe->servings, 'servings');
        $this->assertSame($sent['yield'], $recipe->yield, 'yield');
        $this->assertSame($sent['is_draft'], $recipe->is_draft, 'is_draft');
        $this->assertSame('2026-09-01 10:00', $recipe->published_at?->format('Y-m-d H:i'), 'published_at');
    }

    public function test_creating_keeps_every_field(): void
    {
        $sent = $this->payload();

        $this->post(route('admin.recipes.store'), $sent)->assertRedirect();

        $this->assertEverythingLanded(Recipe::sole(), $sent);
    }

    public function test_updating_keeps_every_field(): void
    {
        $recipe = Recipe::create(['name' => 'Placeholder', 'slug' => 'placeholder']);
        $sent = $this->payload();

        $this->put(route('admin.recipes.update', $recipe), $sent)->assertRedirect();

        $this->assertEverythingLanded($recipe->fresh(), $sent);
    }

    public function test_the_kit_survives_a_create_on_its_own(): void
    {
        // The reported symptom: ticked on the create form, gone afterwards.
        $this->post(route('admin.recipes.store'), [
            'name' => 'Kit only',
            'meal_type' => MealType::Dinner->value,
            'cooking_methods' => [CookingMethod::Skottle->value],
        ])->assertRedirect();

        $this->assertSame(['skottle'], Recipe::sole()->cooking_methods);
    }

    public function test_unticking_everything_clears_it_rather_than_keeping_the_old_set(): void
    {
        $recipe = Recipe::create([
            'name' => 'Had kit',
            'slug' => 'had-kit',
            'cooking_methods' => ['skottle'],
            'dietary' => ['one-pot'],
        ]);

        $this->put(route('admin.recipes.update', $recipe), [
            'name' => 'Had kit',
            'slug' => 'had-kit',
            'meal_type' => MealType::Dinner->value,
            'cooking_methods' => [],
            'dietary' => [],
        ])->assertRedirect();

        $this->assertSame([], $recipe->fresh()->cooking_methods);
        $this->assertSame([], $recipe->fresh()->dietary);
    }

    public function test_the_edit_screen_hands_back_what_was_saved(): void
    {
        $sent = $this->payload();
        $this->post(route('admin.recipes.store'), $sent)->assertRedirect();

        $this->get(route('admin.recipes.edit', Recipe::sole()))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('recipe.cooking_methods', $sent['cooking_methods'])
                ->where('recipe.dietary', $sent['dietary'])
                ->where('recipe.yield', $sent['yield'])
                ->where('recipe.method_title', $sent['method_title'])
                ->where('recipe.difficulty', $sent['difficulty']));
    }
}
