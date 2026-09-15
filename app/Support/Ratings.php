<?php

namespace App\Support;

use App\Models\Recipe;

/**
 * What a recipe's stars add up to, and which of them are allowed to.
 *
 * Stars are part of a review now rather than a thing of their own, so the
 * answer to "which ones count" is the same as the answer to "which reviews
 * are on the page": the ones somebody has read. Nothing reaches the average,
 * the cards or a search result before then.
 *
 * The published figure is stored on the recipe rather than worked out on
 * each read. That is partly speed - a listing page would otherwise be a
 * query per card - and mostly so the page, the card and the structured data
 * are physically incapable of disagreeing. Showing an average the markup
 * does not match is its own way of losing a rich result.
 */
class Ratings
{
    /**
     * Writes the published figure back onto the recipe.
     *
     * Called after anything that could change which reviews are on the page,
     * which in practice means after every moderation decision.
     */
    public static function recount(Recipe $recipe): void
    {
        $count = $recipe->comments()->counted()->count();

        $average = $count === 0
            ? null
            : round((float) $recipe->comments()->counted()->avg('stars'), 2);

        $recipe->forceFill([
            'rating_count' => $count,
            'rating_average' => $average,
        ])->save();
    }

    /**
     * The figure everything shows: the page, the card and the markup.
     *
     * @return array{average: float|null, count: int, stars: array<int, int>}
     */
    public static function summary(Recipe $recipe): array
    {
        $counted = $recipe->relationLoaded('comments')
            ? $recipe->comments->whereNotNull('stars')
            : $recipe->comments()->counted()->get(['stars']);

        return [
            'average' => $recipe->rating_average,
            'count' => (int) $recipe->rating_count,
            // One bucket per star, always all five, so a bar chart has no gaps.
            'stars' => collect(range(1, 5))
                ->mapWithKeys(fn (int $star) => [$star => $counted->where('stars', $star)->count()])
                ->all(),
        ];
    }

    /**
     * Whether this is worth telling a search engine about.
     *
     * Below the threshold the page still shows what it has. It just does not
     * dress one or two people's opinion up as a rating.
     */
    public static function worthPublishing(int $count): bool
    {
        return $count >= (int) config('feedback.ratings.min_for_schema', 3);
    }
}
