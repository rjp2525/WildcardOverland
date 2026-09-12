<?php

namespace App\Support;

use App\Models\Recipe;
use App\Models\Trip;

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
            'image' => $image['src'] ?? null,
            'datePublished' => $recipe->published_at?->toIso8601String(),
            'author' => ['@id' => url('/#reno')],
            'recipeCategory' => $recipe->meal_type->label(),
            'recipeYield' => $recipe->servings
                ? $recipe->servings.' '.str('serving')->plural($recipe->servings)
                : null,
            'prepTime' => static::duration($recipe->prep_minutes),
            'cookTime' => static::duration($recipe->cook_minutes),
            'totalTime' => static::duration($recipe->totalMinutes()),
            'keywords' => static::keywords($recipe),
            'recipeIngredient' => $recipe->ingredients
                ->map(fn ($i) => trim($i->label().($i->note ? ", {$i->note}" : '')))
                ->values()
                ->all(),
            'recipeInstructions' => $recipe->steps
                ->map(fn ($step, $i) => [
                    '@type' => 'HowToStep',
                    'position' => $i + 1,
                    'text' => $step->body,
                ])
                ->values()
                ->all(),
        ], fn ($value) => $value !== null && $value !== []);
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
