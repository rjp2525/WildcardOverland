<?php

namespace App\Enums;

/**
 * What the food actually gets cooked on.
 *
 * Camp cooking is defined by the kit more than the ingredients: the same
 * chilli is a different job in a dutch oven than it is on a skottle. A
 * recipe can name several, because most of them use more than one.
 */
enum CookingMethod: string
{
    case Skottle = 'skottle';
    case Stovetop = 'stovetop';
    case Jetboil = 'jetboil';
    case Skillet = 'skillet';
    case DutchOven = 'dutch-oven';
    case Campfire = 'campfire';
    case Charcoal = 'charcoal';
    case Smoker = 'smoker';

    public function label(): string
    {
        return match ($this) {
            self::Skottle => 'Skottle',
            self::Stovetop => 'Camper stove',
            self::Jetboil => 'Jetboil',
            self::Skillet => 'Cast iron skillet',
            self::DutchOven => 'Dutch oven',
            self::Campfire => 'Campfire',
            self::Charcoal => 'Charcoal grill',
            self::Smoker => 'Smoker',
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
