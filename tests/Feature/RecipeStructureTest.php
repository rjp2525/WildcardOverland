<?php

namespace Tests\Feature;

use App\Enums\SectionKind;
use App\Enums\SectionPlacement;
use App\Enums\TipKind;
use App\Models\Recipe;
use App\Models\User;
use App\Support\RichText\TipTap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A real camp cook has parts, prep done days earlier at home, and asides
 * that belong to one step rather than to the recipe. These cover the shape,
 * not the styling.
 */
class RecipeStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function recipe(array $attributes = []): Recipe
    {
        return Recipe::create([
            'name' => 'Skottle steak fried rice',
            'slug' => 'skottle-steak-fried-rice',
            'is_draft' => false,
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    public function test_ingredients_come_back_under_the_part_they_belong_to(): void
    {
        $recipe = $this->recipe();

        $steak = $recipe->ingredientGroups()->create([
            'order' => 0,
            'name' => 'Steak',
            'note' => TipTap::fromText('The cornstarch is worth bringing.'),
        ]);
        $steak->ingredients()->create([
            'recipe_id' => $recipe->id,
            'order' => 0,
            'quantity' => '3',
            'unit' => 'lb',
            'item' => 'steak',
            'detail' => "Sirloin is the best balance\nRibeye if you are going all out",
        ]);

        $rice = $recipe->ingredientGroups()->create(['order' => 1, 'name' => 'Rice']);
        $rice->ingredients()->create([
            'recipe_id' => $recipe->id,
            'order' => 0,
            'item' => 'day-old white rice',
        ]);

        // Belongs to no part at all.
        $recipe->ingredients()->create(['order' => 0, 'item' => 'neutral oil']);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('recipe.ingredient_groups', 2)
                ->where('recipe.ingredient_groups.0.name', 'Steak')
                ->where('recipe.ingredient_groups.0.note', '<p>The cornstarch is worth bringing.</p>')
                ->where('recipe.ingredient_groups.0.items.0.label', '3 lb steak')
                ->where('recipe.ingredient_groups.0.items.0.details', [
                    'Sirloin is the best balance',
                    'Ribeye if you are going all out',
                ])
                ->where('recipe.ingredient_groups.1.name', 'Rice')
                // The loose list is only what is in no part.
                ->has('recipe.ingredients', 1)
                ->where('recipe.ingredients.0.label', 'neutral oil'));
    }

    public function test_an_optional_ingredient_says_so(): void
    {
        $recipe = $this->recipe();
        $recipe->ingredients()->create(['order' => 0, 'item' => 'corn', 'optional' => true]);
        $recipe->ingredients()->create(['order' => 1, 'item' => 'rice']);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('recipe.ingredients.0.optional', true)
                ->where('recipe.ingredients.1.optional', false));
    }

    public function test_a_step_carries_its_own_tips_in_order(): void
    {
        $recipe = $this->recipe();
        $step = $recipe->steps()->create([
            'order' => 0,
            'title' => 'Garlic and ginger',
            'body' => TipTap::fromText('Stir constantly for 30 to 45 seconds.'),
        ]);

        $step->tips()->create([
            'order' => 1,
            'kind' => TipKind::Tip,
            'body' => TipTap::fromText('Two spatulas helps.'),
        ]);
        $step->tips()->create([
            'order' => 0,
            'kind' => TipKind::Warning,
            'title' => 'Do not walk away',
            'body' => TipTap::fromText('Burnt garlic ruins the lot.'),
        ]);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->where('recipe.steps.0.title', 'Garlic and ginger')
                ->has('recipe.steps.0.tips', 2)
                ->where('recipe.steps.0.tips.0.kind', 'warning')
                ->where('recipe.steps.0.tips.0.title', 'Do not walk away')
                ->where('recipe.steps.0.tips.0.label', 'Watch out')
                ->where('recipe.steps.0.tips.1.kind', 'tip'));
    }

    public function test_a_step_body_renders_as_markup_the_server_wrote(): void
    {
        $recipe = $this->recipe();
        $recipe->steps()->create([
            'order' => 0,
            'body' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph', 'content' => [
                        ['type' => 'text', 'text' => 'Leave it alone for '],
                        ['type' => 'text', 'text' => '60 seconds', 'marks' => [['type' => 'bold']]],
                        ['type' => 'text', 'text' => '.'],
                    ]],
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Then flip it.']]],
                ],
            ],
        ]);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertInertia(fn ($page) => $page->where(
                'recipe.steps.0.body',
                '<p>Leave it alone for <strong>60 seconds</strong>.</p><p>Then flip it.</p>',
            ));
    }

    public function test_sections_are_split_by_where_they_are_read(): void
    {
        $recipe = $this->recipe();

        $recipe->sections()->create([
            'order' => 0,
            'kind' => SectionKind::Prep,
            'placement' => SectionPlacement::BeforeMethod,
            'title' => 'Prep before you leave home',
            'body' => TipTap::fromText('Camping is easier if you do this in your kitchen.'),
        ]);

        $recipe->sections()->create([
            'order' => 1,
            'kind' => SectionKind::Technique,
            'placement' => SectionPlacement::AfterMethod,
            'title' => 'The secret weapon: crispy rice',
        ]);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertInertia(fn ($page) => $page
                ->has('recipe.sections', 2)
                ->where('recipe.sections.0.placement', 'before_method')
                ->where('recipe.sections.0.label', 'Prep at home')
                ->where('recipe.sections.1.placement', 'after_method')
                ->where('recipe.sections.1.kind', 'technique'));
    }

    public function test_a_prose_yield_is_preferred_over_the_servings_count(): void
    {
        $recipe = $this->recipe(['servings' => 10, 'yield' => '10 to 12 big servings']);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertInertia(fn ($page) => $page->where('recipe.yield', '10 to 12 big servings'))
            // And the search engines get told the same thing.
            ->assertSee('10 to 12 big servings', escape: false);
    }

    public function test_the_structured_data_lists_every_ingredient_with_its_part(): void
    {
        $recipe = $this->recipe();
        $sauce = $recipe->ingredientGroups()->create(['order' => 0, 'name' => 'Sauce']);
        $sauce->ingredients()->create([
            'recipe_id' => $recipe->id,
            'order' => 0,
            'quantity' => '2',
            'unit' => 'tbsp',
            'item' => 'soy sauce',
        ]);
        $recipe->ingredients()->create(['order' => 0, 'item' => 'neutral oil']);
        $recipe->steps()->create([
            'order' => 0,
            'title' => 'Sear the steak',
            'body' => TipTap::fromText('Get it hot.'),
        ]);

        $response = $this->get(route('recipes.show', $recipe->slug))->assertOk();

        // Grouped and loose both appear, and the part disambiguates a line
        // that would otherwise read as a duplicate.
        $response->assertSee('2 tbsp soy sauce, for the Sauce', escape: false);
        $response->assertSee('neutral oil', escape: false);
        $response->assertSee('Sear the steak', escape: false);
    }

    public function test_the_admin_writes_the_whole_structure_in_one_save(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.recipes.store'), [
            'name' => 'Skottle steak fried rice',
            'meal_type' => 'dinner',
            'yield' => '10 to 12 big servings',
            'method_title' => 'Cooking it on the Skottle',
            'method_intro' => TipTap::fromText('Dead centre is screaming hot.'),
            'ingredients' => [
                ['item' => 'neutral oil', 'in_shopping_list' => true],
            ],
            'ingredient_groups' => [
                [
                    'name' => 'Steak',
                    'note' => TipTap::fromText('The cornstarch is worth bringing.'),
                    'ingredients' => [
                        ['quantity' => '3', 'unit' => 'lb', 'item' => 'steak', 'detail' => "Sirloin\nRibeye", 'optional' => false],
                        ['item' => 'cornstarch', 'optional' => true, 'in_shopping_list' => false],
                    ],
                ],
            ],
            'steps' => [
                [
                    'title' => 'Sear the steak',
                    'body' => TipTap::fromText("Cook in batches.\n\nLeave it alone for 60 seconds."),
                    'tips' => [
                        [
                            'kind' => 'warning',
                            'title' => 'Do not crowd it',
                            'body' => TipTap::fromText('You will steam it.'),
                        ],
                    ],
                ],
            ],
            'sections' => [
                [
                    'kind' => 'prep',
                    'placement' => 'before_method',
                    'title' => 'Prep before you leave home',
                    'body' => TipTap::fromText('Cook the rice.'),
                ],
            ],
        ])->assertRedirect();

        $recipe = Recipe::firstWhere('name', 'Skottle steak fried rice');

        $this->assertSame('10 to 12 big servings', $recipe->yield);
        $this->assertSame('Cooking it on the Skottle', $recipe->method_title);

        $this->assertCount(1, $recipe->ingredientGroups);
        $group = $recipe->ingredientGroups->first();
        $this->assertSame('Steak', $group->name);
        $this->assertCount(2, $group->ingredients);
        $this->assertSame(['Sirloin', 'Ribeye'], $group->ingredients->first()->details());
        $this->assertTrue($group->ingredients->last()->optional);
        $this->assertFalse($group->ingredients->last()->in_shopping_list);

        // The loose one stays loose.
        $this->assertCount(1, $recipe->looseIngredients);
        // And the flat relation still sees all three, which is what the
        // structured data and the shopping list read from.
        $this->assertCount(3, $recipe->ingredients);

        $step = $recipe->steps->first();
        $this->assertSame('Sear the steak', $step->title);
        $this->assertStringContainsString('<p>Cook in batches.</p>', $step->bodyHtml());
        $this->assertCount(1, $step->tips);
        $this->assertSame(TipKind::Warning, $step->tips->first()->kind);

        $this->assertCount(1, $recipe->sections);
        $this->assertSame(SectionKind::Prep, $recipe->sections->first()->kind);
    }

    public function test_saving_again_replaces_rather_than_duplicates(): void
    {
        $this->actingAs(User::factory()->create());

        $recipe = $this->recipe();
        $payload = [
            'name' => $recipe->name,
            'slug' => $recipe->slug,
            'meal_type' => 'dinner',
            'ingredient_groups' => [
                ['name' => 'Steak', 'ingredients' => [['item' => 'steak']]],
            ],
            'steps' => [
                [
                    'body' => TipTap::fromText('Cook it.'),
                    'tips' => [['kind' => 'tip', 'body' => TipTap::fromText('Two spatulas.')]],
                ],
            ],
        ];

        $this->put(route('admin.recipes.update', $recipe), $payload)->assertRedirect();
        $this->put(route('admin.recipes.update', $recipe), $payload)->assertRedirect();

        $recipe->refresh();

        $this->assertCount(1, $recipe->ingredientGroups);
        $this->assertCount(1, $recipe->ingredients);
        $this->assertCount(1, $recipe->steps);
        $this->assertCount(1, $recipe->steps->first()->tips);
    }

    public function test_removing_a_part_takes_its_ingredients_with_it(): void
    {
        $this->actingAs(User::factory()->create());

        $recipe = $this->recipe();
        $group = $recipe->ingredientGroups()->create(['order' => 0, 'name' => 'Steak']);
        $group->ingredients()->create(['recipe_id' => $recipe->id, 'order' => 0, 'item' => 'steak']);

        $this->put(route('admin.recipes.update', $recipe), [
            'name' => $recipe->name,
            'slug' => $recipe->slug,
            'meal_type' => 'dinner',
        ])->assertRedirect();

        $this->assertCount(0, $recipe->fresh()->ingredientGroups);
        $this->assertCount(0, $recipe->fresh()->ingredients);
    }
}
