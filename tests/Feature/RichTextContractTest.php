<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\RecipeIngredientGroup;
use App\Models\RecipeSection;
use App\Models\RecipeStep;
use App\Models\RecipeStepTip;
use App\Models\Trip;
use App\Support\RichText\TipTap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Which fields are formatted text, and which are deliberately not.
 *
 * Written down as a test because the answer is a decision, not an accident.
 * Prose gets the editor. Anything that is read as words rather than markup
 * stays plain: a card teaser that doubles as the meta description, a line on
 * an ingredient, a label on a source.
 */
class RichTextContractTest extends TestCase
{
    use RefreshDatabase;

    /** table => [column => model class] */
    protected array $rich = [
        'trips' => ['content' => Trip::class],
        'recipes' => ['notes' => Recipe::class, 'method_intro' => Recipe::class],
        'recipe_sections' => ['body' => RecipeSection::class],
        'recipe_steps' => ['body' => RecipeStep::class],
        'recipe_step_tips' => ['body' => RecipeStepTip::class],
        'recipe_ingredient_groups' => ['note' => RecipeIngredientGroup::class],
    ];

    /** Read as words, never as markup. */
    protected array $plain = [
        'trips' => ['summary', 'headline', 'name'],
        'recipes' => ['summary', 'headline', 'name', 'method_title'],
        'recipe_sections' => ['title'],
        'recipe_steps' => ['title'],
        'recipe_step_tips' => ['title'],
        'recipe_ingredient_groups' => ['name'],
        'recipe_ingredients' => ['item', 'note', 'detail'],
        'recipe_sources' => ['label', 'note'],
    ];

    public function test_every_rich_field_is_stored_and_cast_as_a_document(): void
    {
        foreach ($this->rich as $table => $columns) {
            foreach ($columns as $column => $class) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "{$table}.{$column} is missing.",
                );

                /** @var Model $model */
                $model = new $class;

                $this->assertSame(
                    'array',
                    $model->getCasts()[$column] ?? null,
                    "{$table}.{$column} holds a document, so the model has to cast it to an array.",
                );
            }
        }
    }

    public function test_the_section_lead_paragraph_is_part_of_the_body_now(): void
    {
        // One rich field rather than a plain lead and a rich rest, which was
        // two places to decide where a sentence belonged.
        $this->assertFalse(Schema::hasColumn('recipe_sections', 'intro'));
    }

    public function test_plain_fields_stayed_plain(): void
    {
        foreach ($this->plain as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "{$table}.{$column} is missing.",
                );
            }
        }
    }

    public function test_no_public_payload_hands_a_raw_document_to_the_page(): void
    {
        $recipe = Recipe::create([
            'name' => 'Fried rice',
            'slug' => 'fried-rice',
            'is_draft' => false,
            'published_at' => now()->subDay(),
            'notes' => TipTap::fromText('Bring two spatulas.'),
            'method_intro' => TipTap::fromText('Dead centre is screaming hot.'),
        ]);

        $group = $recipe->ingredientGroups()->create([
            'order' => 0,
            'name' => 'Steak',
            'note' => TipTap::fromText('The cornstarch is worth bringing.'),
        ]);
        $group->ingredients()->create(['recipe_id' => $recipe->id, 'order' => 0, 'item' => 'steak']);

        $step = $recipe->steps()->create(['order' => 0, 'body' => TipTap::fromText('Sear it.')]);
        $step->tips()->create(['order' => 0, 'kind' => 'tip', 'body' => TipTap::fromText('Two spatulas.')]);

        $recipe->sections()->create([
            'order' => 0,
            'kind' => 'prep',
            'placement' => 'before_method',
            'title' => 'Prep at home',
            'body' => TipTap::fromText('Cook the rice the day before.'),
        ]);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertOk()
            ->assertInertia(function ($page) {
                /*
                 * Every one of these is a string of markup the server wrote,
                 * never the document. A page that received the document
                 * would have to render it itself, and then there would be
                 * two renderers to keep honest.
                 */
                foreach ([
                    'recipe.notes',
                    'recipe.method_intro',
                    'recipe.ingredient_groups.0.note',
                    'recipe.steps.0.body',
                    'recipe.steps.0.tips.0.body',
                    'recipe.sections.0.body',
                ] as $key) {
                    $page->where($key, fn ($value) => is_string($value) && str_starts_with($value, '<p>'));
                }

                return $page;
            });
    }

    public function test_a_trip_write_up_reaches_the_page_as_markup(): void
    {
        $trip = Trip::create([
            'name' => 'Baja',
            'slug' => 'baja',
            'is_draft' => false,
            'published_at' => now()->subDay(),
            'summary' => 'Two weeks south.',
            'content' => TipTap::fromText('We drove until the road ran out.'),
        ]);

        $this->get(route('trips.show', $trip->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('trip.content', '<p>We drove until the road ran out.</p>')
                // The summary is the meta description, so it stays words.
                ->where('trip.summary', 'Two weeks south.'));
    }

    public function test_a_document_that_slipped_into_the_database_still_cannot_inject_markup(): void
    {
        $recipe = Recipe::create([
            'name' => 'Tampered',
            'slug' => 'tampered',
            'is_draft' => false,
            'published_at' => now()->subDay(),
            'notes' => ['type' => 'doc', 'content' => [
                ['type' => 'paragraph', 'content' => [
                    ['type' => 'text', 'text' => '<img src=x onerror=alert(1)>'],
                    ['type' => 'text', 'text' => 'link', 'marks' => [
                        ['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']],
                    ]],
                ]],
                ['type' => 'script', 'content' => [['type' => 'text', 'text' => 'alert(1)']]],
            ]],
        ]);

        $this->get(route('recipes.show', $recipe->slug))->assertOk();

        $rendered = TipTap::html($recipe->notes);

        /*
         * The dangerous text survives as text, which is the point: it is
         * shown, not run. What must not survive is any of it becoming
         * markup, so these check for tags and attributes rather than for
         * the words, which appear harmlessly escaped.
         */
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $rendered);
        $this->assertStringNotContainsString('<img', $rendered);
        $this->assertStringNotContainsString('<script', $rendered);
        $this->assertStringNotContainsString('href=', $rendered);
        // The unknown node contributed nothing at all.
        $this->assertStringNotContainsString('alert(1)</', $rendered);
    }
}
