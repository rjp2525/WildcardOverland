<?php

namespace App\Support;

use App\Enums\CommentStatus;
use App\Models\Image;
use App\Models\Recipe;
use App\Models\Trip;
use App\Support\RichText\TipTap;

/**
 * JSON-LD for the public pages.
 *
 * Everything here describes something a visitor can actually see on the page
 * it is attached to. Marking up a claim the page does not make is how you
 * lose the rich result and the trust along with it, so nothing is added
 * purely because a schema type allows it.
 */
class StructuredData
{
    /**
     * The person behind the site, referenced by everything else rather than
     * repeated into each page.
     */
    public static function person(): array
    {
        return [
            '@type' => 'Person',
            '@id' => url('/#reno'),
            'name' => 'Reno',
            'alternateName' => Seo::SITE,
            'url' => url('/about'),
        ];
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/#website'),
            'url' => url('/'),
            'name' => Seo::SITE,
            'description' => Seo::DEFAULT_DESCRIPTION,
            'publisher' => ['@id' => url('/#reno')],
        ];
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $crumbs
     */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ], $crumbs, array_keys($crumbs)),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $image
     */
    public static function trip(Trip $trip, ?array $image): array
    {
        return array_filter([
            '@type' => 'BlogPosting',
            'headline' => $trip->name,
            'description' => $trip->summary ?: $trip->headline,
            'image' => $image['src'] ?? null,
            'datePublished' => $trip->published_at?->toIso8601String(),
            'dateModified' => $trip->updated_at?->toIso8601String(),
            'author' => ['@id' => url('/#reno')],
            'publisher' => ['@id' => url('/#reno')],
            'mainEntityOfPage' => route('trips.show', $trip->slug),
        ]);
    }

    /**
     * A recipe is the one thing on this site with a genuine rich result
     * behind it, so it is worth marking up completely.
     *
     * @param  array<string, mixed>|null  $image
     */
    public static function recipe(Recipe $recipe, ?array $image): array
    {
        return array_filter([
            '@type' => 'Recipe',
            'name' => $recipe->name,
            'description' => $recipe->summary ?: $recipe->headline,
            /*
             * Several shapes of the same photograph. Google picks whichever
             * fits the result it is building, and offering only one means
             * it either crops ours or uses none.
             */
            'image' => static::imageSet($recipe->heroImage, $image),
            'datePublished' => $recipe->published_at?->toIso8601String(),
            'dateModified' => $recipe->updated_at?->toIso8601String(),
            'mainEntityOfPage' => route('recipes.show', $recipe->slug),
            'author' => ['@id' => url('/#reno')],
            'recipeCategory' => $recipe->meal_type->label(),
            // The prose yield when there is one: "10 to 12 big servings"
            // tells a reader more than the number 10 does.
            'recipeYield' => $recipe->yield ?: ($recipe->servings
                ? $recipe->servings.' '.str('serving')->plural($recipe->servings)
                : null),
            'prepTime' => static::duration($recipe->prep_minutes),
            'cookTime' => static::duration($recipe->cook_minutes),
            'totalTime' => static::duration($recipe->totalMinutes()),
            'keywords' => static::keywords($recipe),
            /*
             * Only once enough people have rated it. A star in a search
             * result is a claim that somebody stands behind the number, and
             * one rating from one browser is not that.
             */
            'aggregateRating' => static::aggregateRating($recipe),
            /*
             * The comments, but only the ones whose author also left stars.
             * A review in this markup is a rating with words attached, and
             * a Review with no reviewRating is the shape Google quietly
             * drops rather than the extra detail it looks like.
             */
            'review' => static::reviews($recipe),
            // The kit, which the page shows and which is half the recipe out
            // here. cookingMethod is the words, tool is the things.
            'cookingMethod' => static::cookingMethod($recipe),
            'tool' => array_map(
                fn ($method) => ['@type' => 'HowToTool', 'name' => $method->label()],
                $recipe->cookingMethods(),
            ),
            'suitableForDiet' => array_values(array_filter(array_map(
                fn ($tag) => $tag->schemaDiet(),
                $recipe->dietaryTags(),
            ))),
            /*
             * Flat, and with the part folded into the line. The spec has no
             * grouping for ingredients, so "2 tbsp soy sauce" from the steak
             * and from the sauce would otherwise read as a duplicate.
             */
            'recipeIngredient' => $recipe->ingredients
                ->map(fn ($i) => trim(implode(', ', array_filter([
                    $i->label(),
                    $i->note,
                    $i->group?->name ? "for the {$i->group->name}" : null,
                ]))))
                ->values()
                ->all(),
            'recipeInstructions' => $recipe->steps
                ->map(fn ($step, $i) => array_filter([
                    '@type' => 'HowToStep',
                    'position' => $i + 1,
                    'name' => $step->title,
                    // Words only. The spec wants an instruction, not markup.
                    'text' => TipTap::text($step->body),
                ], fn ($value) => $value !== null))
                ->values()
                ->all(),
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * The reviews on the page, as reviews.
     *
     * Every one carries its own stars now that leaving them is part of
     * writing one, which is the shape this markup wants: a Review with no
     * reviewRating is the thing Google quietly drops rather than the extra
     * detail it looks like.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function reviews(Recipe $recipe): array
    {
        $reviews = $recipe->relationLoaded('comments')
            ? $recipe->comments->where('status', CommentStatus::Approved)->whereNotNull('stars')
            : $recipe->comments()->counted()->get();

        return $reviews
            ->map(fn ($review) => [
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $review->name],
                'datePublished' => ($review->approved_at ?? $review->created_at)?->toDateString(),
                'reviewBody' => $review->body,
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => $review->stars,
                    'bestRating' => 5,
                    'worstRating' => 1,
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * The things a listing page is listing, in the order it lists them.
     *
     * Describes the page that is actually served, so a paginated listing
     * describes its own page rather than claiming the whole collection.
     *
     * @param  array<int, array{name: string, url: string}>  $items
     * @return array<string, mixed>|null
     */
    public static function itemList(array $items, string $name): ?array
    {
        if ($items === []) {
            return null;
        }

        return [
            '@type' => 'ItemList',
            'name' => $name,
            'numberOfItems' => count($items),
            'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
            'itemListElement' => array_map(fn (array $item, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'url' => $item['url'],
            ], $items, array_keys($items)),
        ];
    }

    /**
     * The stars, if there are enough of them to mean anything.
     *
     * @return array<string, mixed>|null
     */
    protected static function aggregateRating(Recipe $recipe): ?array
    {
        $summary = Ratings::summary($recipe);

        if (! Ratings::worthPublishing($summary['count'])) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => $summary['average'],
            'ratingCount' => $summary['count'],
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }

    /**
     * The same photograph at the shapes a rich result might want.
     *
     * Nothing when there is no photograph. The drawn link card is a title
     * over a background rather than a picture of the food, so offering it
     * here would be describing a dish nobody can see. The cost is that a
     * recipe without a hero photo gets no recipe rich result, which is the
     * honest outcome and a reason to go and take one.
     *
     * @param  array<string, mixed>|null  $presented
     * @return array<int, string>|null
     */
    protected static function imageSet(?Image $image, ?array $presented): ?array
    {
        // Private images are never presented, and this must not go around
        // that: ImagePresenter returning null is the check.
        if ($image === null || $presented === null || $image->file === null) {
            return null;
        }

        return [
            AssetUrl::image($image->file, 'hero', 1280),
            AssetUrl::image($image->file, 'card', 960),
            AssetUrl::image($image->file, 'thumb', 640),
        ];
    }

    protected static function cookingMethod(Recipe $recipe): ?string
    {
        $methods = array_map(fn ($method) => $method->label(), $recipe->cookingMethods());

        return $methods === [] ? null : implode(', ', $methods);
    }

    /** ISO 8601 duration, which is the only format the spec accepts. */
    protected static function duration(?int $minutes): ?string
    {
        return $minutes ? 'PT'.$minutes.'M' : null;
    }

    protected static function keywords(Recipe $recipe): ?string
    {
        $words = [
            ...array_map(fn ($tag) => $tag->label(), $recipe->dietaryTags()),
            ...array_map(fn ($method) => $method->label(), $recipe->cookingMethods()),
        ];

        return $words === [] ? null : implode(', ', $words);
    }

    /**
     * Wraps the page's entries in one graph, with the site and the author
     * always present so the rest has something to point at.
     *
     * @param  array<int, array<string, mixed>>  $entries
     */
    public static function graph(array $entries): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => [static::website(), static::person(), ...$entries],
        ];
    }
}
