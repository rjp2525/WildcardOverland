<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RatingStatus;
use App\Http\Controllers\Controller;
use App\Models\RecipeRating;
use App\Support\AdminTable;
use App\Support\Ratings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The ratings nothing published, and why.
 *
 * Anonymous stars are the easiest thing on the site to manufacture, so the
 * rule is that anything which cannot be told apart from an attempt to move
 * the number waits here instead of counting. Both of the rules that put it
 * here can be wrong about a real person, which is the whole reason this
 * screen exists rather than the rating being thrown away.
 */
class RatingController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->value() ?: RatingStatus::Held->value;

        if (! in_array($status, array_column(RatingStatus::cases(), 'value'), true)) {
            $status = RatingStatus::Held->value;
        }

        $table = AdminTable::for(
            RecipeRating::query()->where('status', $status)->with('recipe:id,name,slug,rating_count,rating_average'),
            $request,
        )->sortable(['stars', 'created_at'], 'created_at');

        return Inertia::render('admin/ratings/Index', [
            'ratings' => $table->paginate()->through(fn (RecipeRating $rating) => [
                'id' => $rating->id,
                'stars' => $rating->stars,
                'status' => $rating->status->value,
                'posted' => $rating->created_at?->toDayDateTimeString(),
                'recipe' => $rating->recipe?->name,
                'recipeUrl' => $rating->recipe
                    ? route('recipes.show', $rating->recipe->slug)
                    : null,
                // What the recipe is publishing without this one.
                'published' => [
                    'average' => $rating->recipe?->rating_average,
                    'count' => (int) ($rating->recipe?->rating_count ?? 0),
                ],
                /*
                 * Why it is here. The same address having already voted is
                 * one person with two identities or two people on one wifi,
                 * and the difference is not something code can see.
                 */
                'alsoFromAddress' => $rating->ip_hash === null ? 0 : RecipeRating::query()
                    ->where('ip_hash', $rating->ip_hash)
                    ->whereKeyNot($rating->id)
                    ->count(),
            ]),
            'filters' => $table->state(),
            'status' => $status,
            'statuses' => RatingStatus::options(),
            'counts' => RecipeRating::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    /**
     * Lets one through, or throws it out.
     *
     * Either way the recipe's published figure is written again straight
     * afterwards, because that stored number is what the page shows and what
     * the structured data claims.
     */
    public function update(Request $request, RecipeRating $rating): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(RatingStatus::class)],
        ]);

        $rating->update(['status' => RatingStatus::from($validated['status'])]);

        $rating->loadMissing('recipe');

        if ($rating->recipe !== null) {
            Ratings::recount($rating->recipe);
        }

        return back()->with('success', match ($rating->status) {
            RatingStatus::Counted => 'Counted. The recipe has been added up again.',
            RatingStatus::Held => 'Held back.',
            RatingStatus::Discounted => 'Thrown out.',
        });
    }
}
