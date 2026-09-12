<?php

namespace App\Enums;

enum DietaryTag: string
{
    case Vegetarian = 'vegetarian';
    case Vegan = 'vegan';
    case GlutenFree = 'gluten-free';
    case DairyFree = 'dairy-free';
    case OnePot = 'one-pot';
    case NoCook = 'no-cook';
    case MakeAhead = 'make-ahead';

    public function label(): string
    {
        return match ($this) {
            self::GlutenFree => 'Gluten free',
            self::DairyFree => 'Dairy free',
            self::OnePot => 'One pot',
            self::NoCook => 'No cook',
            self::MakeAhead => 'Make ahead',
            default => ucfirst($this->value),
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

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
