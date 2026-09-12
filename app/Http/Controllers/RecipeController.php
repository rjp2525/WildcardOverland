<?php

namespace App\Http\Controllers;

use App\Enums\MealType;
use App\Models\Recipe;
use App\Support\ImagePresenter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'meal' => ['nullable', Rule::enum(MealType::class)],
        ]);

        $meal = isset($validated['meal']) ? MealType::from($validated['meal']) : null;

        $recipes = Recipe::published()
            ->with('heroImage.file')
            ->when($meal, fn ($query) => $query->where('meal_type', $meal))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Recipe $recipe) => static::cardFor($recipe));

        return Inertia::render('recipes/Index', [
            'recipes' => $recipes,
            'mealTypes' => MealType::options(),
            'activeMeal' => $meal?->value,
        ]);
    }

    public function show(Recipe $recipe): Response
    {
        abort_unless($this->isPublished($recipe), 404);

        $recipe->load(['heroImage.file', 'ingredients', 'steps.image.file', 'sources']);

        return Inertia::render('recipes/Show', [
            'recipe' => [
                'name' => $recipe->name,
                'headline' => $recipe->headline,
                'summary' => $recipe->summary,
                'notes' => $recipe->notes,
                'meal_type' => $recipe->meal_type->label(),
                'difficulty' => $recipe->difficulty?->label(),
                'dietary' => array_map(fn ($tag) => $tag->label(), $recipe->dietaryTags()),
                'prep_minutes' => $recipe->prep_minutes,
                'cook_minutes' => $recipe->cook_minutes,
                'total_minutes' => $recipe->totalMinutes(),
                'servings' => $recipe->servings,
                'hero' => ImagePresenter::hero($recipe->heroImage, $recipe->name),
                'ingredients' => $recipe->ingredients->map(fn ($i) => [
                    'label' => $i->label(),
                    'note' => $i->note,
                ]),
                'steps' => $recipe->steps->map(fn ($step) => [
                    'body' => $step->body,
                    'note' => $step->note,
                    'image' => ImagePresenter::thumb($step->image, "Step {$step->order}"),
                ]),
                'sources' => $recipe->sources->map(fn ($source) => [
                    'kind' => $source->kind->label(),
                    'label' => $source->label,
                    'url' => $source->url,
                    'note' => $source->note,
                ]),
            ],
            'more' => Recipe::published()
                ->whereKeyNot($recipe->id)
                ->with('heroImage.file')
                ->orderByDesc('published_at')
                ->take(3)
                ->get()
                ->map(fn (Recipe $other) => static::cardFor($other)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function cardFor(Recipe $recipe): array
    {
        return [
            'name' => $recipe->name,
            'slug' => $recipe->slug,
            'headline' => $recipe->headline,
            'url' => route('recipes.show', $recipe->slug),
            'meal_type' => $recipe->meal_type->label(),
            'difficulty' => $recipe->difficulty?->label(),
            'total_minutes' => $recipe->totalMinutes(),
            'servings' => $recipe->servings,
            'dietary' => array_map(fn ($tag) => $tag->label(), $recipe->dietaryTags()),
            'image' => ImagePresenter::card($recipe->heroImage, $recipe->name),
        ];
    }

    protected function isPublished(Recipe $recipe): bool
    {
        return ! $recipe->is_draft
            && $recipe->published_at !== null
            && $recipe->published_at->isPast();
    }
}
