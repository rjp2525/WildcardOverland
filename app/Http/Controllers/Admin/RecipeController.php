<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CookingMethod;
use App\Enums\DietaryTag;
use App\Enums\Difficulty;
use App\Enums\MealType;
use App\Enums\SectionKind;
use App\Enums\SectionPlacement;
use App\Enums\SourceKind;
use App\Enums\TipKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecipeRequest;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Support\AdminTable;
use App\Support\ImageOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function index(Request $request): Response
    {
        $table = AdminTable::for(Recipe::query(), $request)
            ->trashable()
            ->searchable(['name', 'headline', 'slug'])
            ->sortable(['name', 'meal_type', 'published_at', 'created_at'], 'created_at');

        return Inertia::render('admin/recipes/Index', [
            'recipes' => $table->paginate()->through(fn (Recipe $recipe) => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'headline' => $recipe->headline,
                'meal_type' => $recipe->meal_type->label(),
                'difficulty' => $recipe->difficulty?->label(),
                'total_minutes' => $recipe->totalMinutes(),
                'servings' => $recipe->servings,
                'yield' => $recipe->yield,
                'is_draft' => $recipe->is_draft,
                'deleted_at' => $recipe->deleted_at?->toDateTimeString(),
            ]),
            'filters' => $table->state(),
            'trashedCount' => Recipe::onlyTrashed()->count(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/recipes/Edit', [
            'recipe' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(RecipeRequest $request): RedirectResponse
    {
        $recipe = DB::transaction(function () use ($request): Recipe {
            $recipe = Recipe::create($request->safe()->except(['ingredients', 'ingredient_groups', 'steps', 'sections', 'sources']));

            $this->syncChildren($recipe, $request);

            return $recipe;
        });

        return redirect()
            ->route('admin.recipes.edit', $recipe)
            ->with('success', "Recipe \"{$recipe->name}\" created.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function ingredientRow(RecipeIngredient $ingredient): array
    {
        return [
            'quantity' => $ingredient->quantity,
            'unit' => $ingredient->unit,
            'item' => $ingredient->item,
            'note' => $ingredient->note,
            'detail' => $ingredient->detail,
            'optional' => $ingredient->optional,
            'in_shopping_list' => $ingredient->in_shopping_list,
        ];
    }

    public function edit(Recipe $recipe): Response
    {
        $recipe->load(['looseIngredients', 'ingredientGroups.ingredients', 'steps.tips', 'sections', 'sources']);

        return Inertia::render('admin/recipes/Edit', [
            'recipe' => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'slug' => $recipe->slug,
                'headline' => $recipe->headline,
                'hero_image_id' => $recipe->hero_image_id,
                'summary' => $recipe->summary,
                'notes' => $recipe->notes,
                'method_title' => $recipe->method_title,
                'method_intro' => $recipe->method_intro,
                'meal_type' => $recipe->meal_type->value,
                'difficulty' => $recipe->difficulty?->value,
                'dietary' => $recipe->dietary ?? [],
                'prep_minutes' => $recipe->prep_minutes,
                'cook_minutes' => $recipe->cook_minutes,
                'servings' => $recipe->servings,
                'yield' => $recipe->yield,
                'is_draft' => $recipe->is_draft,
                'published_at' => $recipe->published_at?->format('Y-m-d\TH:i'),
                'ingredients' => $recipe->looseIngredients->map($this->ingredientRow(...)),
                'ingredient_groups' => $recipe->ingredientGroups->map(fn ($group) => [
                    'name' => $group->name,
                    'note' => $group->note,
                    'ingredients' => $group->ingredients->map($this->ingredientRow(...)),
                ]),
                'sections' => $recipe->sections->map(fn ($section) => [
                    'kind' => $section->kind->value,
                    'placement' => $section->placement->value,
                    'title' => $section->title,
                    'intro' => $section->intro,
                    'body' => $section->body,
                ]),
                'sources' => $recipe->sources->map(fn ($source) => [
                    'kind' => $source->kind->value,
                    'label' => $source->label,
                    'url' => $source->url,
                    'note' => $source->note,
                ]),
                'steps' => $recipe->steps->map(fn ($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'body' => $s->body,
                    'image_id' => $s->image_id,
                    'tips' => $s->tips->map(fn ($tip) => [
                        'kind' => $tip->kind->value,
                        'title' => $tip->title,
                        'body' => $tip->body,
                    ]),
                ]),
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(RecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        DB::transaction(function () use ($request, $recipe): void {
            $recipe->update($request->safe()->except(['ingredients', 'ingredient_groups', 'steps', 'sections', 'sources']));

            $this->syncChildren($recipe, $request);
        });

        return back()->with('success', 'Recipe updated.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $recipe->delete();

        return redirect()
            ->route('admin.recipes.index')
            ->with('success', "\"{$recipe->name}\" is in the trash. You can still restore it.");
    }

    public function restore(Recipe $recipe): RedirectResponse
    {
        $recipe->restore();

        return redirect()
            ->route('admin.recipes.index')
            ->with('success', "\"{$recipe->name}\" is back.");
    }

    /**
     * Deletes the recipe for good, along with its ingredients, steps,
     * sources and any link to a trip. All of those cascade in the database.
     */
    public function forceDestroy(Recipe $recipe): RedirectResponse
    {
        $recipe->forceDelete();

        return redirect()
            ->route('admin.recipes.index', ['trashed' => 'only'])
            ->with('success', "\"{$recipe->name}\" is gone for good.");
    }

    /**
     * Replace ingredients, sources and steps with the submitted sets,
     * renumbering as they arrive so the form's order is what gets stored.
     */
    protected function syncChildren(Recipe $recipe, RecipeRequest $request): void
    {
        /*
         * Deleting the groups takes their ingredients with them, so the
         * loose ones have to go separately or they would survive as
         * orphans of a part that no longer exists.
         */
        $recipe->ingredientGroups()->delete();
        $recipe->ingredients()->delete();

        $this->writeIngredients($recipe, $request->validated('ingredients', []));

        foreach (array_values($request->validated('ingredient_groups', [])) as $order => $group) {
            $written = $recipe->ingredientGroups()->create([
                'order' => $order,
                'name' => $group['name'],
                'note' => $group['note'] ?? null,
            ]);

            $this->writeIngredients($recipe, $group['ingredients'] ?? [], $written->id);
        }

        $recipe->sections()->delete();
        foreach (array_values($request->validated('sections', [])) as $order => $row) {
            $recipe->sections()->create([
                'order' => $order,
                'kind' => $row['kind'],
                'placement' => $row['placement'],
                'title' => $row['title'],
                'intro' => $row['intro'] ?? null,
                'body' => $row['body'] ?? null,
            ]);
        }

        $recipe->sources()->delete();
        foreach (array_values($request->validated('sources', [])) as $order => $row) {
            $recipe->sources()->create([
                'order' => $order,
                'kind' => $row['kind'],
                'label' => $row['label'],
                'url' => $row['url'] ?? null,
                'note' => $row['note'] ?? null,
            ]);
        }

        $recipe->steps()->delete();
        foreach (array_values($request->validated('steps', [])) as $order => $row) {
            $step = $recipe->steps()->create([
                'order' => $order,
                'title' => $row['title'] ?? null,
                'body' => $row['body'],
                'image_id' => $row['image_id'] ?? null,
            ]);

            foreach (array_values($row['tips'] ?? []) as $tipOrder => $tip) {
                $step->tips()->create([
                    'order' => $tipOrder,
                    'kind' => $tip['kind'],
                    'title' => $tip['title'] ?? null,
                    'body' => $tip['body'],
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function writeIngredients(Recipe $recipe, array $rows, ?int $groupId = null): void
    {
        foreach (array_values($rows) as $order => $row) {
            $recipe->ingredients()->create([
                'group_id' => $groupId,
                'order' => $order,
                'quantity' => $row['quantity'] ?? null,
                'unit' => $row['unit'] ?? null,
                'item' => $row['item'],
                'note' => $row['note'] ?? null,
                'detail' => $row['detail'] ?? null,
                'optional' => $row['optional'] ?? false,
                'in_shopping_list' => $row['in_shopping_list'] ?? true,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'mealTypes' => MealType::options(),
            'difficulties' => Difficulty::options(),
            'dietaryTags' => DietaryTag::options(),
            'cookingMethods' => CookingMethod::options(),
            'sourceKinds' => SourceKind::options(),
            'sectionKinds' => SectionKind::options(),
            'sectionPlacements' => SectionPlacement::options(),
            'tipKinds' => TipKind::options(),
            'images' => ImageOptions::list(),
        ];
    }
}
