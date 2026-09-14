<?php

namespace App\Enums;

/**
 * Whether a section is read before the cooking starts or after it.
 *
 * This is the only placement choice that matters. Prep you have to have read
 * days ago; a tip about crispy rice is useless if it appears after you have
 * already served dinner.
 */
enum SectionPlacement: string
{
    case BeforeMethod = 'before_method';
    case AfterMethod = 'after_method';

    public function label(): string
    {
        return match ($this) {
            self::BeforeMethod => 'Before the method',
            self::AfterMethod => 'After the method',
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
