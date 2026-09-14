<?php

namespace App\Enums;

/**
 * Whether a rating is behind the number on the page.
 *
 * Everything sent in is kept. What is published is a narrower set, because
 * a search result carrying stars is a claim that somebody stands behind
 * them, and the cost of publishing a number somebody manufactured is losing
 * the rich result and the trust that goes with it.
 */
enum RatingStatus: string
{
    /** In the published average. */
    case Counted = 'counted';

    /**
     * Kept, not counted, waiting to be looked at. Set automatically when a
     * rating cannot be told apart from an attempt to move the number: a
     * second identity from an address that has already voted, or one of a
     * sudden run of them on the same recipe.
     */
    case Held = 'held';

    /** Looked at and thrown out. Never counts. */
    case Discounted = 'discounted';

    public function label(): string
    {
        return match ($this) {
            self::Counted => 'Counted',
            self::Held => 'Held back',
            self::Discounted => 'Thrown out',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
