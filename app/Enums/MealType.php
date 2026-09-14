<?php

namespace App\Enums;

/**
 * What a recipe is for.
 *
 * A fixed list in code rather than a table. It changes about once a year,
 * every case is referred to by name somewhere, and the value is written into
 * each recipe's row, so a database backed version would buy an admin screen
 * nobody needs at the cost of the compiler no longer knowing these exist.
 *
 * The order here is the order the filters appear in.
 */
enum MealType: string
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';
    case SideDish = 'side-dish';
    case Dessert = 'dessert';
    case Snack = 'snack';
    case Drink = 'drink';

    /** Spelled out, because the value is a slug and "Side-dish" is not a word. */
    public function label(): string
    {
        return match ($this) {
            self::SideDish => 'Side dish',
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
}
