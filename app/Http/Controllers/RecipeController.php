<?php

namespace App\Http\Controllers;

use App\Enums\MealType;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Support\Honeypot;
use App\Support\ImagePresenter;
use App\Support\Ratings;
use App\Support\RelatedRecipes;
use App\Support\RichText\TipTap;
use App\Support\Seo;
use App\Support\StructuredData;
use App\Support\Visitor;
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
            ->withRatingSummary()
            ->with('heroImage.file')
            ->when($meal, fn ($query) => $query->where('meal_type', $meal))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Recipe $recipe) => static::cardFor($recipe));

        return Inertia::render('recipes/Index', [
            'seo' => Seo::make(
                title: $meal ? $meal->label().' recipes' : 'Camp Recipes',
                description: 'Food worth making a long way from a kitchen. One pot where it can be, a skottle or a dutch oven where it cannot.',
                card: route('og.card', ['kind' => 'page', 'slug' => 'recipes']),
                canonical: $meal ? route('recipes.index', ['meal' => $meal->value]) : route('recipes.index'),
                schema: array_values(array_filter([
                    StructuredData::breadcrumbs([
                        ['name' => 'Home', 'url' => route('homepage')],
                        ['name' => 'Camp recipes', 'url' => route('recipes.index')],
                    ]),
                    // What this page of the listing is actually listing.
                    StructuredData::itemList(
                        collect($recipes->items())->map(fn ($card) => [
                            'name' => $card['name'],
                            'url' => route('recipes.show', $card['slug']),
                        ])->all(),
                        $meal ? $meal->label().' recipes' : 'Camp recipes',
                    ),
                ])),
            ),
            'recipes' => $recipes,
            'mealTypes' => MealType::options(),
            'activeMeal' => $meal?->value,
        ]);
    }

    /**
     * One ingredient as the page needs it.
     *
     * @return array<string, mixed>
     */
    protected static function ingredientPayload(RecipeIngredient $ingredient): array
    {
        return [
            'label' => $ingredient->label(),
            'note' => $ingredient->note,
            'details' => $ingredient->details(),
            'optional' => $ingredient->optional,
            'shopping' => $ingredient->in_shopping_list,
        ];
    }

    public function show(Request $request, Recipe $recipe): Response
    {
        abort_unless($this->isPublished($recipe), 404);

        $recipe->load([
            'heroImage.file',
            'looseIngredients',
            'ingredientGroups.ingredients',
            // The flat set, with its part, is what the structured data uses.
            'ingredients.group',
            'steps.image.file',
            'steps.tips',
            'sections',
            'sources',
            'ratings',
            // Only what is on the page; the queue is nobody else's business.
            'comments' => fn ($query) => $query->approved()->with('image.file'),
        ]);

        $hero = ImagePresenter::hero($recipe->heroImage, $recipe->name);

        return Inertia::render('recipes/Show', [
            'seo' => Seo::make(
                title: $recipe->name,
                description: $recipe->summary ?: $recipe->headline,
                card: route('og.card', ['kind' => 'recipes', 'slug' => $recipe->slug]),
                type: 'article',
                canonical: route('recipes.show', $recipe->slug),
                article: [
                    'published' => $recipe->published_at?->toIso8601String(),
                    'modified' => $recipe->updated_at?->toIso8601String(),
                    'section' => $recipe->meal_type->label(),
                    'tags' => [
                        ...array_map(fn ($tag) => $tag->label(), $recipe->dietaryTags()),
                        ...array_map(fn ($method) => $method->label(), $recipe->cookingMethods()),
                    ],
                ],
                schema: [
                    StructuredData::recipe($recipe, $hero),
                    StructuredData::breadcrumbs([
                        ['name' => 'Home', 'url' => route('homepage')],
                        ['name' => 'Camp recipes', 'url' => route('recipes.index')],
                        ['name' => $recipe->name, 'url' => route('recipes.show', $recipe->slug)],
                    ]),
                ],
            ),
            'recipe' => [
                // Identifies this recipe's saved checklist in the browser.
                'slug' => $recipe->slug,
                'name' => $recipe->name,
                'headline' => $recipe->headline,
                'summary' => $recipe->summary,
                'notes' => TipTap::html($recipe->notes),
                'meal_type' => $recipe->meal_type->label(),
                'difficulty' => $recipe->difficulty?->label(),
                'dietary' => array_map(fn ($tag) => $tag->label(), $recipe->dietaryTags()),
                'cooked_on' => array_map(
                    fn ($method) => ['value' => $method->value, 'label' => $method->label()],
                    $recipe->cookingMethods(),
                ),
                'prep_minutes' => $recipe->prep_minutes,
                'cook_minutes' => $recipe->cook_minutes,
                'total_minutes' => $recipe->totalMinutes(),
                'servings' => $recipe->servings,
                'yield' => $recipe->yield,
                'method_title' => $recipe->method_title ?: 'Method',
                'method_intro' => TipTap::html($recipe->method_intro),
                'hero' => $hero,
                /*
                 * Parts first, then anything that belongs to no part. A
                 * recipe with no parts at all still comes out as one plain
                 * list, which is what a short one should look like.
                 */
                'ingredient_groups' => $recipe->ingredientGroups
                    ->map(fn ($group) => [
                        'name' => $group->name,
                        'note' => TipTap::html($group->note),
                        'items' => $group->ingredients->map(static::ingredientPayload(...))->values(),
                    ])
                    ->values(),
                'ingredients' => $recipe->looseIngredients->map(static::ingredientPayload(...))->values(),
                'steps' => $recipe->steps->map(fn ($step) => [
                    'title' => $step->title,
                    'body' => $step->bodyHtml(),
                    'tips' => $step->tips->map(fn ($tip) => [
                        'kind' => $tip->kind->value,
                        'label' => $tip->kind->label(),
                        'title' => $tip->title,
                        'body' => TipTap::html($tip->body),
                    ])->values(),
                    /*
                     * Two renderings of the same photograph: the crop the
                     * column shows, and the whole frame behind it for when
                     * someone wants to see what the pan actually looked like.
                     */
                    'image' => $step->image === null ? null : [
                        'crop' => ImagePresenter::step($step->image, $step->title ?? "Step {$step->order}"),
                        'full' => ImagePresenter::full($step->image, $step->title ?? "Step {$step->order}"),
                    ],
                ]),
                'sections' => $recipe->sections->map(fn ($section) => [
                    'kind' => $section->kind->value,
                    'label' => $section->kind->label(),
                    'placement' => $section->placement->value,
                    'title' => $section->title,
                    'body' => TipTap::html($section->body),
                ]),
                'sources' => $recipe->sources->map(fn ($source) => [
                    'kind' => $source->kind->label(),
                    'label' => $source->label,
                    'url' => $source->url,
                    'note' => $source->note,
                ]),
            ],
            // Scored on what they have in common rather than on which is
            // newest, which was never a relationship.
            'feedback' => [
                'rating' => Ratings::summary($recipe),
                /*
                 * Their own star, if this browser has left one, so the
                 * widget comes back set rather than empty. Read-only: a
                 * cookie is issued when somebody sends something in, not
                 * to everybody who turns up to read.
                 */
                'yours' => ($hash = Visitor::existing($request)) === null ? null : $recipe->ratings
                    ->firstWhere('visitor_hash', $hash)?->stars,
                'comments' => $recipe->comments->map(fn ($comment) => [
                    'name' => $comment->name,
                    'body' => $comment->body,
                    'posted' => $comment->approved_at?->toDateString()
                        ?? $comment->created_at?->toDateString(),
                    // Their stars, if they left some, shown beside their words.
                    'stars' => $comment->visitor_hash === null ? null : $recipe->ratings
                        ->firstWhere('visitor_hash', $comment->visitor_hash)?->stars,
                    'photo' => [
                        'crop' => ImagePresenter::step($comment->image, "Photo from {$comment->name}"),
                        'full' => ImagePresenter::full($comment->image, "Photo from {$comment->name}"),
                    ],
                ])->values(),
                // A fresh stamp per render, which is half of the honeypot.
                'stamp' => Honeypot::stamp(),
                'trap' => Honeypot::FIELD,
                'stampField' => Honeypot::STAMP,
                'photos' => (bool) config('feedback.comments.photos'),
                'moderated' => (bool) config('feedback.comments.moderate'),
                'maxLength' => (int) config('feedback.comments.max_length'),
                'photoMaxKb' => (int) config('feedback.comments.photo_max_kb'),
            ],
            'more' => RelatedRecipes::for($recipe)
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
            /*
             * Only where the listing query asked for them, and only once
             * there are enough of them to be worth showing. A card carrying
             * "5.0 from 1" is not a recommendation, it is one person.
             */
            'rating' => Ratings::worthPublishing((int) ($recipe->ratings_count ?? 0))
                ? [
                    'average' => round((float) $recipe->ratings_avg_stars, 2),
                    'count' => (int) $recipe->ratings_count,
                ]
                : null,
            'difficulty' => $recipe->difficulty?->label(),
            'total_minutes' => $recipe->totalMinutes(),
            'servings' => $recipe->servings,
            'dietary' => array_map(fn ($tag) => $tag->label(), $recipe->dietaryTags()),
            'cooked_on' => array_map(
                fn ($method) => ['value' => $method->value, 'label' => $method->label()],
                $recipe->cookingMethods(),
            ),
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
