<?php

namespace App\Support;

use App\Enums\RatingStatus;
use App\Models\Recipe;
use App\Models\RecipeRating;
use Illuminate\Http\Request;

/**
 * What a recipe's stars add up to, and which of them are allowed to.
 *
 * Two different questions, deliberately answered separately: what has been
 * sent in, which is every row, and what the site is willing to say, which is
 * only the rows that cannot be told apart from somebody who actually cooked
 * the thing.
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
     * Takes somebody's stars, and decides whether they count.
     *
     * Changing your mind updates the rating you already left and keeps
     * whatever standing it had, so a held rating cannot be laundered into a
     * counted one by sending it again.
     */
    public static function record(Recipe $recipe, int $stars, Request $request): RecipeRating
    {
        $visitor = Visitor::identify($request);
        $address = Visitor::addressHash($request);

        $rating = $recipe->ratings()->where('visitor_hash', $visitor)->first();

        if ($rating !== null) {
            $rating->update(['stars' => $stars, 'ip_hash' => $address]);
        } else {
            $rating = $recipe->ratings()->create([
                'stars' => $stars,
                'visitor_hash' => $visitor,
                'ip_hash' => $address,
                'status' => static::standingFor($recipe, $address),
            ]);
        }

        static::recount($recipe);

        return $rating;
    }

    /**
     * Whether a rating nobody has looked at yet is allowed to count.
     *
     * Both rules are about the one thing a cookie cannot survive: somebody
     * deciding to be several people. Neither throws anything away, because
     * either can be wrong about a real person - two of you on one campsite
     * wifi, or a recipe that genuinely got shared somewhere busy - and both
     * are visible in the admin so a real one can be let through.
     */
    protected static function standingFor(Recipe $recipe, ?string $address): RatingStatus
    {
        /*
         * An address that has already had its say. Clearing your cookies
         * gets you a new identity; it does not get you a second vote.
         */
        $voted = $address !== null && $recipe->ratings()
            ->where('ip_hash', $address)
            ->where('status', RatingStatus::Counted)
            ->exists();

        if ($voted) {
            return RatingStatus::Held;
        }

        /*
         * A sudden run on one recipe, which is what being bought looks like.
         * Well above anything a personal site sees on its best day, so in
         * normal weather this never fires.
         */
        $recent = $recipe->ratings()
            ->where('created_at', '>=', now()->subMinutes((int) config('feedback.ratings.burst_minutes')))
            ->count();

        return $recent >= (int) config('feedback.ratings.burst_limit')
            ? RatingStatus::Held
            : RatingStatus::Counted;
    }

    /**
     * Writes the published figure back onto the recipe.
     *
     * Called after anything that could change which ratings count, including
     * somebody in the admin changing their mind about one.
     */
    public static function recount(Recipe $recipe): void
    {
        $count = $recipe->ratings()->where('status', RatingStatus::Counted)->count();

        $average = $count === 0 ? null : round(
            (float) $recipe->ratings()->where('status', RatingStatus::Counted)->avg('stars'),
            2,
        );

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
        $counted = $recipe->relationLoaded('ratings')
            ? $recipe->ratings->where('status', RatingStatus::Counted)
            : $recipe->ratings()->where('status', RatingStatus::Counted)->get(['stars']);

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
