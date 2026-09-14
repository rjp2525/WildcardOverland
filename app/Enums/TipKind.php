<?php

namespace App\Enums;

/**
 * How seriously to take an aside attached to a step.
 *
 * A tip makes dinner better. A warning is the difference between fried rice
 * and burnt garlic, so it does not get to look like ordinary advice.
 */
enum TipKind: string
{
    case Tip = 'tip';
    case Warning = 'warning';
    case Why = 'why';

    public function label(): string
    {
        return match ($this) {
            self::Tip => 'Tip',
            self::Warning => 'Watch out',
            self::Why => 'Why it works',
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
