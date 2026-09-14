<?php

namespace App\Support;

use App\Models\Recipe;
use Illuminate\Support\Collection;

/**
 * What to cook next, chosen by how much a recipe has in common with this one.
 *
 * The page used to show the three most recent, which is not a relationship.
 * Someone reading a Skottle dinner is very likely to want another Skottle
 * dinner, and quite likely to want one that uses the steak and rice they
 * already bought.
 *
 * Scored rather than filtered: filtering on any single thing empties the
 * list as soon as a site has a handful of recipes in it.
 */
class RelatedRecipes
{
    /**
     * Kit is the strongest signal out here. Owning a Skottle is a bigger
     * thing about a person than liking dinner.
     */
    protected const WEIGHT_METHOD = 5;

    protected const WEIGHT_MEAL_TYPE = 4;

    protected const WEIGHT_INGREDIENT = 2;

    /** Capped so a long shared shopping list cannot swamp everything else. */
    protected const MAX_INGREDIENT_SCORE = 10;

    protected const WEIGHT_DIETARY = 1;

    protected const WEIGHT_SAME_TRIP = 3;

    /**
     * Words that say nothing about what a dish is: units, packaging and the
     * things every camp recipe carries anyway.
     *
     * @var array<int, string>
     */
    protected const NOISE = [
        'cup', 'cups', 'tbsp', 'tsp', 'tablespoon', 'tablespoons', 'teaspoon',
        'teaspoons', 'lb', 'lbs', 'pound', 'pounds', 'oz', 'ounce', 'ounces',
        'g', 'kg', 'ml', 'l', 'litre', 'liter', 'clove', 'cloves', 'can',
        'cans', 'jar', 'packet', 'pack', 'bunch', 'large', 'small', 'medium',
        'fresh', 'dried', 'frozen', 'chopped', 'diced', 'sliced', 'minced',
        'grated', 'ground', 'cooked', 'raw', 'whole', 'and', 'or', 'of',
        'for', 'the', 'a', 'to', 'with', 'optional', 'plus', 'extra', 'more',
        'salt', 'pepper', 'water', 'oil',
    ];

    /**
     * @return Collection<int, Recipe>
     */
    public static function for(Recipe $recipe, int $limit = 3): Collection
    {
        $candidates = Recipe::published()
            ->whereKeyNot($recipe->id)
            ->withRatingSummary()
            ->with(['heroImage.file', 'ingredients:id,recipe_id,item', 'trips:id'])
            ->get();

        if ($candidates->isEmpty()) {
            return $candidates;
        }

        $recipe->loadMissing(['ingredients:id,recipe_id,item', 'trips:id']);

        $mine = [
            'methods' => array_map(fn ($m) => $m->value, $recipe->cookingMethods()),
            'diet' => array_map(fn ($t) => $t->value, $recipe->dietaryTags()),
            'ingredients' => static::ingredientTerms($recipe),
            'trips' => $recipe->trips->pluck('id')->all(),
        ];

        return $candidates
            ->map(fn (Recipe $other) => [
                'recipe' => $other,
                'score' => static::score($recipe, $mine, $other),
            ])
            ->sortByDesc(fn (array $row) => [
                $row['score'],
                // A tie goes to the newer one, which is the old behaviour and
                // a reasonable answer when nothing else separates them.
                $row['recipe']->published_at?->getTimestamp() ?? 0,
            ])
            ->take($limit)
            ->map(fn (array $row) => $row['recipe'])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $mine
     */
    protected static function score(Recipe $recipe, array $mine, Recipe $other): int
    {
        $score = 0;

        $theirMethods = array_map(fn ($m) => $m->value, $other->cookingMethods());
        $score += count(array_intersect($mine['methods'], $theirMethods)) * static::WEIGHT_METHOD;

        if ($recipe->meal_type === $other->meal_type) {
            $score += static::WEIGHT_MEAL_TYPE;
        }

        $shared = count(array_intersect($mine['ingredients'], static::ingredientTerms($other)));
        $score += min($shared * static::WEIGHT_INGREDIENT, static::MAX_INGREDIENT_SCORE);

        $theirDiet = array_map(fn ($t) => $t->value, $other->dietaryTags());
        $score += count(array_intersect($mine['diet'], $theirDiet)) * static::WEIGHT_DIETARY;

        // Cooked on the same trip is a real relationship, not a guess.
        if (array_intersect($mine['trips'], $other->trips->pluck('id')->all()) !== []) {
            $score += static::WEIGHT_SAME_TRIP;
        }

        return $score;
    }

    /**
     * The words in a recipe's ingredients that actually identify a dish.
     *
     * "2 tbsp toasted sesame oil" and "1 tsp sesame oil" should count as the
     * same thing, so quantities, units and preparation words are dropped and
     * what is left is compared as a set.
     *
     * @return array<int, string>
     */
    protected static function ingredientTerms(Recipe $recipe): array
    {
        $terms = [];

        foreach ($recipe->ingredients as $ingredient) {
            $words = preg_split('/[^a-z]+/', strtolower((string) $ingredient->item)) ?: [];

            foreach ($words as $word) {
                if (strlen($word) < 3 || in_array($word, static::NOISE, true)) {
                    continue;
                }

                $terms[] = static::singular($word);
            }
        }

        return array_values(array_unique($terms));
    }

    /** Crude on purpose: "eggs" and "egg" have to meet somewhere. */
    protected static function singular(string $word): string
    {
        if (str_ends_with($word, 'ies') && strlen($word) > 4) {
            return substr($word, 0, -3).'y';
        }

        if (str_ends_with($word, 'es') && strlen($word) > 4) {
            return substr($word, 0, -2);
        }

        return str_ends_with($word, 's') && ! str_ends_with($word, 'ss') && strlen($word) > 3
            ? substr($word, 0, -1)
            : $word;
    }
}
