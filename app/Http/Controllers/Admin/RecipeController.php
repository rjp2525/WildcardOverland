<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DietaryTag;
use App\Enums\Difficulty;
use App\Enums\MealType;
use App\Enums\SourceKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecipeRequest;
use App\Models\Image;
use App\Models\Recipe;
use App\Support\AdminTable;
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
                'is_draft' => $recipe->is_draft,
            ]),
            'filters' => $table->state(),
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
            $recipe = Recipe::create($request->safe()->except(['ingredients', 'steps', 'sources']));

            $this->syncChildren($recipe, $request);

            return $recipe;
        });

        return redirect()
            ->route('admin.recipes.edit', $recipe)
            ->with('success', "Recipe \"{$recipe->name}\" created.");
    }

    public function edit(Recipe $recipe): Response
    {
        return Inertia::render('admin/recipes/Edit', [
            'recipe' => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'slug' => $recipe->slug,
                'headline' => $recipe->headline,
                'hero_image_id' => $recipe->hero_image_id,
                'summary' => $recipe->summary,
                'notes' => $recipe->notes,
                'meal_type' => $recipe->meal_type->value,
                'difficulty' => $recipe->difficulty?->value,
                'dietary' => $recipe->dietary ?? [],
                'prep_minutes' => $recipe->prep_minutes,
                'cook_minutes' => $recipe->cook_minutes,
                'servings' => $recipe->servings,
                'is_draft' => $recipe->is_draft,
                'published_at' => $recipe->published_at?->format('Y-m-d\TH:i'),
                'ingredients' => $recipe->ingredients->map(fn ($i) => [
                    'id' => $i->id,
                    'quantity' => $i->quantity,
                    'unit' => $i->unit,
                    'item' => $i->item,
                    'note' => $i->note,
                ]),
                'sources' => $recipe->sources->map(fn ($source) => [
                    'kind' => $source->kind->value,
                    'label' => $source->label,
                    'url' => $source->url,
                    'note' => $source->note,
                ]),
                'steps' => $recipe->steps->map(fn ($s) => [
                    'id' => $s->id,
                    'body' => $s->body,
                    'note' => $s->note,
                    'image_id' => $s->image_id,
                ]),
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(RecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        DB::transaction(function () use ($request, $recipe): void {
            $recipe->update($request->safe()->except(['ingredients', 'steps', 'sources']));

            $this->syncChildren($recipe, $request);
        });

        return back()->with('success', 'Recipe updated.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $recipe->delete();

        return redirect()
            ->route('admin.recipes.index')
            ->with('success', "Recipe \"{$recipe->name}\" deleted.");
    }

    /**
     * Replace ingredients, sources and steps with the submitted sets,
     * renumbering as they arrive so the form's order is what gets stored.
     */
    protected function syncChildren(Recipe $recipe, RecipeRequest $request): void
    {
        $recipe->ingredients()->delete();
        foreach (array_values($request->validated('ingredients', [])) as $order => $row) {
            $recipe->ingredients()->create([
                'order' => $order,
                'quantity' => $row['quantity'] ?? null,
                'unit' => $row['unit'] ?? null,
                'item' => $row['item'],
                'note' => $row['note'] ?? null,
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
            $recipe->steps()->create([
                'order' => $order,
                'body' => $row['body'],
                'note' => $row['note'] ?? null,
                'image_id' => $row['image_id'] ?? null,
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
            'sourceKinds' => SourceKind::options(),
            'images' => Image::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Image $image) => [
                    'value' => $image->id,
                    'label' => $image->name ?: "Image #{$image->id}",
                ]),
        ];
    }
}
