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
     * The schema.org RestrictedDiet this tag is the same claim as.
     *
     * Only the three that map exactly. "Dairy free" is a stricter claim than
     * LowLactoseDiet and the rest are about how a recipe is cooked rather
     * than what is in it, so marking any of them up would be saying
     * something the page does not.
     */
    public function schemaDiet(): ?string
    {
        return match ($this) {
            self::Vegetarian => 'https://schema.org/VegetarianDiet',
            self::Vegan => 'https://schema.org/VeganDiet',
            self::GlutenFree => 'https://schema.org/GlutenFreeDiet',
            default => null,
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
