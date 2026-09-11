<?php

namespace App\Enums;

/**
 * What an image depicts.
 *
 * Distinct from File::$type (content vs static), which describes the file's
 * role in the site rather than its subject. A partner logo and a trip
 * photograph can both be "static" files while counting very differently.
 */
enum ImageType: string
{
    case Photo = 'photo';
    case Logo = 'logo';
    case Graphic = 'graphic';

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Photograph',
            self::Logo => 'Logo',
            self::Graphic => 'Graphic',
        };
    }

    /**
     * Options for a select input.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
