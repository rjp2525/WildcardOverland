<?php

namespace App\Enums;

/**
 * Parallax depth a part sits at on the build illustration. Ordered back to
 * front; the depth drives how far each layer shifts.
 */
enum BuildLayer: string
{
    case Roof = 'roof';
    case Body = 'body';
    case Interior = 'interior';
    case Underside = 'underside';

    public function label(): string
    {
        return match ($this) {
            self::Roof => 'Roof & rack',
            self::Body => 'Body & exterior',
            self::Interior => 'Interior & storage',
            self::Underside => 'Underside & drivetrain',
        };
    }

    /** How far this layer shifts, relative to the deepest. */
    public function depth(): float
    {
        return match ($this) {
            self::Roof => 1.0,
            self::Body => 0.65,
            self::Interior => 0.4,
            self::Underside => 0.2,
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
