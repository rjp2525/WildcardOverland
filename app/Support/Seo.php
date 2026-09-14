<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * The head of a public page, assembled in one place.
 *
 * These tags are rendered by the Blade root template rather than by Inertia,
 * because the things that read them do not run JavaScript. A crawler or a
 * link unfurler asks for the HTML once and takes what is in it, so the tags
 * have to be in that first response whether or not server rendering is on.
 */
class Seo
{
    public const SITE = 'Wildcard Overland';

    /** The same name the Person in the structured data carries. */
    public const AUTHOR = 'Reno';

    /** Ends up in the tab, in search results and on the link card. */
    public const DEFAULT_DESCRIPTION = 'A Toyota Tacoma, a camper on the back and whatever road looks interesting. Trip write-ups, camp cooking and the whole build, part by part.';

    /**
     * @param  string|null  $card  A route('og.card') URL
     * @param  array<int, array<string, mixed>>  $schema  JSON-LD graph entries
     * @param  array<string, mixed>|null  $article  published, modified, section, tags
     * @return array<string, mixed>
     */
    public static function make(
        string $title,
        ?string $description = null,
        ?string $card = null,
        string $type = 'website',
        ?string $canonical = null,
        bool $index = true,
        array $schema = [],
        ?array $article = null,
    ): array {
        return [
            'title' => static::title($title),
            'description' => static::trim($description ?? static::DEFAULT_DESCRIPTION),
            'canonical' => $canonical ?? url()->current(),
            'type' => $type,
            'index' => $index,
            'site' => static::SITE,
            'image' => [
                'url' => $card ?? route('og.card', ['kind' => 'page', 'slug' => 'home']),
                'width' => 1200,
                'height' => 630,
                'alt' => $title.' on Wildcard Overland',
            ],
            'schema' => $schema,
            /*
             * Open Graph's article properties. They are what a link unfurler
             * and a news reader use to say how old a thing is and what it is
             * about, and they cost nothing on a page that already knows.
             */
            'article' => $article === null ? null : array_filter([
                'published' => $article['published'] ?? null,
                'modified' => $article['modified'] ?? null,
                'section' => $article['section'] ?? null,
                'tags' => array_values(array_filter($article['tags'] ?? [])),
            ], fn ($value) => $value !== null && $value !== []),
        ];
    }

    /**
     * The site name goes on the end of every title except the homepage's,
     * which already carries it.
     */
    protected static function title(string $title): string
    {
        return str_contains($title, static::SITE)
            ? $title
            : $title.' | '.static::SITE;
    }

    /**
     * Descriptions get cut on the way out anyway. Cutting here keeps a
     * sentence from being sliced mid-word by whatever is doing the cutting.
     */
    protected static function trim(string $description): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($description))), 165);
    }
}
