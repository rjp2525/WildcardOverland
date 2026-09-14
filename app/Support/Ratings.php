<?php

namespace App\Support;

use App\Models\Recipe;

/**
 * What a recipe's stars add up to.
 *
 * Two different questions, deliberately answered separately: what the page
 * shows, which is everything there is, and what the structured data is
 * willing to claim, which is only a figure with enough behind it to be worth
 * putting in a search result.
 */
class Ratings
{
    /**
     * @return array{average: float|null, count: int, stars: array<int, int>}
     */
    public static function summary(Recipe $recipe): array
    {
        $ratings = $recipe->relationLoaded('ratings')
            ? $recipe->ratings
            : $recipe->ratings()->get(['stars']);

        $count = $ratings->count();

        // One bucket per star, always all five, so a bar chart has no gaps.
        $stars = collect(range(1, 5))
            ->mapWithKeys(fn (int $star) => [$star => $ratings->where('stars', $star)->count()])
            ->all();

        return [
            'average' => $count === 0 ? null : round($ratings->avg('stars'), 2),
            'count' => $count,
            'stars' => $stars,
        ];
    }

    /**
     * Whether this is worth telling a search engine about.
     *
     * Below the threshold the page still shows what it has. It just does not
     * dress one person's opinion up as a rating.
     */
    public static function worthPublishing(int $count): bool
    {
        return $count >= (int) config('feedback.ratings.min_for_schema', 3);
    }
}
