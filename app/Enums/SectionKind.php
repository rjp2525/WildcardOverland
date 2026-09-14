<?php

namespace App\Enums;

/**
 * The extra parts of a camp recipe that are not ingredients or steps.
 *
 * Each one is read at a different moment: prep happens in your kitchen days
 * earlier, logistics while you are packing the truck, tips while you are
 * standing over the burner. The kind is what tells the page how to treat it.
 */
enum SectionKind: string
{
    case Prep = 'prep';
    case Technique = 'technique';
    case Tips = 'tips';
    case Logistics = 'logistics';
    case Scaling = 'scaling';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Prep => 'Prep at home',
            self::Technique => 'Technique',
            self::Tips => 'Tips',
            self::Logistics => 'Packing and logistics',
            self::Scaling => 'Scaling it up',
            self::Note => 'Note',
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
