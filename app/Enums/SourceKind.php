<?php

namespace App\Enums;

/**
 * How a recipe came to be. Credit where it is due, and a note of what the
 * original was, since almost nothing here was invented from scratch.
 */
enum SourceKind: string
{
    case Found = 'found';
    case Inspired = 'inspired';
    case Adapted = 'adapted';
    case Technique = 'technique';

    public function label(): string
    {
        return match ($this) {
            self::Found => 'Found it at',
            self::Inspired => 'Inspired by',
            self::Adapted => 'Adapted from',
            self::Technique => 'Technique from',
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
