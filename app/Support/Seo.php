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

    /** Ends up in the tab, in search results and on the link card. */
    public const DEFAULT_DESCRIPTION = 'A Toyota Tacoma, a camper on the back and whatever road looks interesting. Trip write-ups, camp cooking and the whole build, part by part.';

    /**
     * @param  string|null  $card  A route('og.card') URL
     * @param  array<int, array<string, mixed>>  $schema  JSON-LD graph entries
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
