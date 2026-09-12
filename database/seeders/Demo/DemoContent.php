<?php

namespace Database\Seeders\Demo;

use App\Enums\DietaryTag;
use App\Enums\Difficulty;
use App\Enums\MealType;

/**
 * The single definition of what the demo seeder creates.
 *
 * DemoContentSeeder and DemoContentCleanupSeeder both read from here, so the
 * cleanup can never fall out of step with what was seeded. Slugs are the
 * identity: cleanup removes exactly these and nothing else.
 *
 * Coordinates are real places, and each campsite's state is set directly
 * rather than geocoded - a seeder should not depend on a network call.
 */
class DemoContent
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function trips(): array
    {
        return [
            [
                'slug' => 'alvord-desert-crossing',
                'name' => 'Alvord Desert Crossing',
                'recipes' => ['Dutch Oven Chili', 'Cowboy Coffee'],
                'headline' => 'Three days on a dry lake bed in south-east Oregon',
                'miles' => 412,
                'start_date' => '2025-05-09',
                'end_date' => '2025-05-12',
                'summary' => 'Hot springs, a playa you can drive across at speed and not another vehicle for a day and a half.',
                'content' => '<h2>Getting there</h2><p>The last fuel is a long way back, so we topped off twice and carried an extra twenty litres. The washboard on the approach road is the worst part of the whole trip.</p><h2>On the playa</h2><p>Flat, white and absolutely silent. We aired down to 18psi and did a slow lap of the edge before setting up somewhere near the middle.</p><ul><li>Bring more shade than you think</li><li>The wind picks up hard around four</li></ul>',
                'image' => 'alvord-desert',
                'campsites' => [
                    ['name' => 'Alvord Hot Springs', 'lat' => 42.5436, 'lng' => -118.5320, 'state' => 'Oregon', 'country' => 'US', 'nights' => 1, 'notes' => 'Soak at sunrise before anyone else arrives.'],
                    ['name' => 'Playa Centre', 'lat' => 42.4900, 'lng' => -118.5600, 'state' => 'Oregon', 'country' => 'US', 'nights' => 2, 'notes' => 'No shade whatsoever. Worth it.'],
                ],
            ],
            [
                'slug' => 'mojave-road',
                'name' => 'Mojave Road',
                'recipes' => ['Foil Packet Trout', 'Skillet Cornbread'],
                'headline' => 'The full 140 miles, east to west',
                'miles' => 296,
                'start_date' => '2025-03-14',
                'end_date' => '2025-03-17',
                'summary' => 'An old wagon route across the Mojave, now a rough two-track with a mailbox halfway along.',
                'content' => '<h2>Why east to west</h2><p>You finish at the Colorado River rather than starting there, and the light is better behind you all day.</p><p>The sand near Soda Dry Lake is the only place we needed to air down properly.</p><blockquote>Sign the register at the mailbox. Everyone does.</blockquote>',
                'image' => 'mojave-road',
                'campsites' => [
                    ['name' => 'Afton Canyon', 'lat' => 35.0392, 'lng' => -116.3833, 'state' => 'California', 'country' => 'US', 'nights' => 1, 'notes' => 'Trains all night. Bring earplugs.'],
                    ['name' => 'Marl Springs', 'lat' => 35.1300, 'lng' => -115.6500, 'state' => 'California', 'country' => 'US', 'nights' => 1],
                    ['name' => 'Piute Creek', 'lat' => 35.1100, 'lng' => -114.9800, 'state' => 'California', 'country' => 'US', 'nights' => 1, 'notes' => 'Water year round, which is rare out here.'],
                ],
            ],
            [
                'slug' => 'white-rim-trail',
                'name' => 'White Rim Trail',
                'recipes' => ['Camp Breakfast Hash', 'Overnight Oats'],
                'headline' => 'A hundred miles of shelf road under Island in the Sky',
                'miles' => 188,
                'start_date' => '2025-09-22',
                'end_date' => '2025-09-25',
                'summary' => 'Permit-only, no water and some of the best camping anywhere in Utah.',
                'content' => '<h2>Permits</h2><p>Book the moment the window opens. The good sites go in minutes.</p><h2>Shafer switchbacks</h2><p>Steeper than the photographs suggest, and the drop is right there. Low range, first gear, no drama.</p>',
                'image' => 'white-rim',
                'campsites' => [
                    ['name' => 'Airport Camp', 'lat' => 38.4230, 'lng' => -109.8000, 'state' => 'Utah', 'country' => 'US', 'nights' => 1],
                    ['name' => 'Murphy Hogback', 'lat' => 38.3700, 'lng' => -109.9200, 'state' => 'Utah', 'country' => 'US', 'nights' => 2, 'notes' => 'Best sunset on the whole loop.'],
                ],
            ],
            [
                'slug' => 'alpine-loop',
                'name' => 'Alpine Loop',
                'recipes' => ['Dutch Oven Chili', 'Skillet Cornbread', 'Cowboy Coffee'],
                'headline' => 'Over Cinnamon and Engineer in the same day',
                'miles' => 164,
                'start_date' => '2025-07-18',
                'end_date' => '2025-07-20',
                'summary' => 'Two twelve-thousand-foot passes, an old mining town and afternoon storms you can set your watch by.',
                'content' => '<h2>Weather</h2><p>Be over the passes by noon. The build-up starts early and the exposed sections are no place to be in a storm.</p><p>Animas Forks is worth an hour on its own.</p>',
                'image' => 'alpine-loop',
                'campsites' => [
                    ['name' => 'Mineral Creek', 'lat' => 37.8300, 'lng' => -107.7000, 'state' => 'Colorado', 'country' => 'US', 'nights' => 1, 'notes' => 'Cold. Properly cold, in July.'],
                    ['name' => 'Animas Forks', 'lat' => 37.9300, 'lng' => -107.5700, 'state' => 'Colorado', 'country' => 'US', 'nights' => 1],
                ],
            ],
            [
                'slug' => 'mogollon-rim',
                'name' => 'Mogollon Rim',
                'recipes' => ['Camp Breakfast Hash'],
                'headline' => 'Pines, and a two-thousand-foot edge to camp on',
                'miles' => 137,
                'start_date' => '2025-06-06',
                'end_date' => '2025-06-08',
                'summary' => 'An easy weekend run with campsites right on the rim, a couple of hours from Phoenix.',
                'content' => '<p>Rim Road runs most of the length of the escarpment, and almost every pull-off has a view. Easy enough for any vehicle in the dry.</p><h2>Fire</h2><p>Restrictions come in early most years. Check before you leave, and carry a stove regardless.</p>',
                'image' => 'mogollon-rim',
                'campsites' => [
                    ['name' => 'Rim Road Overlook', 'lat' => 34.4200, 'lng' => -111.1500, 'state' => 'Arizona', 'country' => 'US', 'nights' => 2, 'notes' => 'Pick a spot away from the road for the quiet.'],
                ],
            ],
            [
                'slug' => 'baja-peninsula',
                'name' => 'Baja Peninsula',
                'recipes' => ['Foil Packet Trout', 'Overnight Oats', 'Cowboy Coffee'],
                'headline' => 'Nine days down to Bahía Concepción and back',
                'miles' => 1284,
                'start_date' => '2026-03-01',
                'end_date' => '2026-03-09',
                'summary' => 'Fish tacos, empty beaches you can park on and the best water on the whole trip.',
                'content' => '<h2>Crossing</h2><p>Tecate is calmer than Tijuana and the paperwork took twenty minutes. Get the vehicle permit sorted before you go.</p><h2>The beaches</h2><p>Playa Santispac is the famous one, but the coves either side are quieter and cost nothing.</p><ul><li>Fuel is cash in a lot of places</li><li>The topes will destroy you if you are not watching</li></ul>',
                'image' => 'baja-peninsula',
                'campsites' => [
                    ['name' => 'Gonzaga Bay', 'lat' => 29.7981, 'lng' => -114.3956, 'state' => 'Baja California', 'country' => 'MX', 'nights' => 3, 'notes' => 'Windy but worth it.'],
                    ['name' => 'Bahía Concepción', 'lat' => 26.6000, 'lng' => -111.8000, 'state' => 'Baja California Sur', 'country' => 'MX', 'nights' => 4, 'notes' => 'Park on the sand, swim before coffee.'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function recipes(): array
    {
        return [
            [
                'slug' => 'dutch-oven-chili',
                'name' => 'Dutch Oven Chili',
                'headline' => 'One pot, and better the second night',
                'meal_type' => MealType::Dinner,
                'difficulty' => Difficulty::Easy,
                'dietary' => [DietaryTag::OnePot->value, DietaryTag::GlutenFree->value],
                'prep' => 15, 'cook' => 45, 'servings' => 4,
                'image' => 'recipe-chili',
                'summary' => 'The one that gets made on almost every trip. Forgiving, filling and it reheats better than it has any right to.',
                'notes' => '<p>Swap the beef for another tin of beans and it is just as good. If you are at altitude, give it another twenty minutes.</p>',
                'ingredients' => [
                    ['1', 'lb', 'ground beef', null],
                    ['1', null, 'onion', 'diced'],
                    ['2', 'cans', 'kidney beans', 'drained'],
                    ['1', 'can', 'chopped tomatoes', null],
                    ['2', 'tbsp', 'chilli powder', null],
                    [null, null, 'salt', 'to taste'],
                ],
                'steps' => [
                    ['Brown the beef in the dutch oven over a good bed of coals.', 'Coals, not flame. Flame scorches the bottom before the middle knows about it.'],
                    ['Add the onion and cook until it goes soft, about five minutes.', null],
                    ['Tip in everything else, stir and put the lid on.', null],
                    ['Simmer for forty-five minutes, stirring whenever you walk past.', 'If it looks dry, a splash of water. It should not need it.'],
                ],
                'sources' => [
                    ['found', 'Serious Eats', 'https://www.seriouseats.com', 'Their dutch oven method is the one I copy'],
                    ['inspired', "My grandmother's chili", null, 'Hers had a whole cinnamon stick in it'],
                ],
            ],
            [
                'slug' => 'camp-breakfast-hash',
                'name' => 'Camp Breakfast Hash',
                'headline' => 'Whatever is left, in one skillet',
                'meal_type' => MealType::Breakfast,
                'difficulty' => Difficulty::Easy,
                'dietary' => [DietaryTag::OnePot->value, DietaryTag::GlutenFree->value],
                'prep' => 10, 'cook' => 20, 'servings' => 2,
                'image' => 'recipe-hash',
                'summary' => 'The last-morning breakfast that clears out the cooler.',
                'notes' => '<p>A cast iron skillet is worth the weight for this alone. Get it properly hot before the potatoes go in.</p>',
                'ingredients' => [
                    ['3', null, 'potatoes', 'diced small'],
                    ['1', null, 'onion', 'diced'],
                    ['4', null, 'eggs', null],
                    [null, null, 'whatever else is in the cooler', null],
                ],
                'steps' => [
                    'Fry the potatoes in plenty of oil until the edges go properly golden.',
                    'Add the onion and anything else that needs using up.',
                    'Make four wells, crack in the eggs, cover and leave until set.',
                ],
            ],
            [
                'slug' => 'foil-packet-trout',
                'name' => 'Foil Packet Trout',
                'headline' => 'Straight onto the coals, nothing to wash up',
                'meal_type' => MealType::Dinner,
                'difficulty' => Difficulty::Medium,
                'dietary' => [DietaryTag::GlutenFree->value, DietaryTag::DairyFree->value],
                'prep' => 10, 'cook' => 18, 'servings' => 2,
                'image' => 'recipe-trout',
                'summary' => 'If you caught it that afternoon, this is the only thing to do with it.',
                'notes' => '<p>Double the foil. A packet that splits into the fire is a sad end to a good fish.</p>',
                'ingredients' => [
                    ['2', null, 'whole trout', 'gutted'],
                    ['1', null, 'lemon', 'sliced'],
                    ['2', 'sprigs', 'thyme', null],
                    ['2', 'tbsp', 'olive oil', null],
                ],
                'steps' => [
                    'Lay each fish on a doubled sheet of foil.',
                    'Stuff the cavity with lemon and thyme, then oil and season the outside.',
                    'Seal the packets loosely and set them straight on the coals.',
                    'Eighteen minutes, turning once. The flesh should come away from the bone.',
                ],
            ],
            [
                'slug' => 'overnight-oats',
                'name' => 'Overnight Oats',
                'headline' => 'Made the night before, eaten cold at dawn',
                'meal_type' => MealType::Breakfast,
                'difficulty' => Difficulty::Easy,
                'dietary' => [DietaryTag::Vegetarian->value, DietaryTag::NoCook->value, DietaryTag::MakeAhead->value],
                'prep' => 5, 'cook' => 0, 'servings' => 2,
                'image' => 'recipe-oats',
                'summary' => 'For mornings when you want to be moving before the sun is properly up.',
                'notes' => '<p>Keeps two days in a cold cooler. Any longer and it goes claggy.</p>',
                'ingredients' => [
                    ['1', 'cup', 'rolled oats', null],
                    ['1', 'cup', 'milk', 'or oat milk'],
                    ['2', 'tbsp', 'peanut butter', null],
                    ['1', 'tbsp', 'honey', null],
                ],
                'steps' => [
                    'Stir everything together in a sealable jar.',
                    'Leave it in the cooler overnight.',
                    'Eat cold, straight from the jar.',
                ],
            ],
            [
                'slug' => 'skillet-cornbread',
                'name' => 'Skillet Cornbread',
                'headline' => 'The thing that makes the chili a meal',
                'meal_type' => MealType::Snack,
                'difficulty' => Difficulty::Medium,
                'dietary' => [DietaryTag::Vegetarian->value],
                'prep' => 10, 'cook' => 25, 'servings' => 6,
                'image' => 'recipe-cornbread',
                'summary' => 'Cooked in the same skillet as everything else, which is rather the point.',
                'notes' => '<p>Getting even heat off coals takes practice. Rotate the skillet a quarter turn every five minutes.</p>',
                'ingredients' => [
                    ['1', 'cup', 'cornmeal', null],
                    ['1', 'cup', 'flour', null],
                    ['1', 'tbsp', 'baking powder', null],
                    ['1', 'cup', 'milk', null],
                    ['1', null, 'egg', null],
                ],
                'steps' => [
                    'Mix the dry, then the wet, then bring them together. Do not overwork it.',
                    'Pour into a hot, greased skillet.',
                    'Cover and bake over low coals for twenty-five minutes.',
                ],
            ],
            [
                'slug' => 'cowboy-coffee',
                'name' => 'Cowboy Coffee',
                'headline' => 'No filter, no press, no excuses',
                'meal_type' => MealType::Drink,
                'difficulty' => Difficulty::Easy,
                'dietary' => [DietaryTag::Vegan->value, DietaryTag::GlutenFree->value],
                'prep' => 2, 'cook' => 8, 'servings' => 4,
                'image' => 'recipe-coffee',
                'summary' => 'Grounds in the pot, cold water to settle them and nobody complains.',
                'notes' => '<p>The splash of cold water at the end really does drop the grounds. Pour slowly and stop before the bottom.</p>',
                'ingredients' => [
                    ['4', 'cups', 'water', null],
                    ['4', 'tbsp', 'coarse ground coffee', null],
                    [null, 'splash', 'cold water', 'to settle'],
                ],
                'steps' => [
                    'Bring the water to a boil, then pull it off the heat for thirty seconds.',
                    'Stir in the coffee and let it steep four minutes.',
                    'Add a splash of cold water to drop the grounds, then pour slowly.',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function modifications(): array
    {
        // `layer` and `hotspot` place a part on the rig illustration;
        // x and y are percentages of that artwork's box.
        return [
            ['name' => 'Prinsu Roof Rack', 'vendor' => 'Prinsu', 'description' => 'Full length aluminium rack over the camper, wind deflector up front.', 'install_date' => '2024-04-12', 'cost' => 124999, 'url' => 'https://prinsu.com', 'timeline' => true, 'layer' => 'roof', 'hotspot' => [19.8, 15.8]],
            ['name' => 'Baja Designs Light Bar', 'vendor' => 'Baja Designs', 'description' => 'Forty inches across the cab roof for the last hour of a long day.', 'install_date' => '2024-05-20', 'cost' => 79900, 'timeline' => false, 'layer' => 'roof', 'hotspot' => [47.5, 24.0]],
            ['name' => 'Renogy 200W Solar', 'vendor' => 'Renogy', 'description' => 'Two panels flat on the rack. Keeps the batteries topped up without idling.', 'install_date' => '2024-06-08', 'cost' => 42000, 'timeline' => false, 'layer' => 'roof', 'hotspot' => [19.6, 13.5]],
            ['name' => 'Tune M1L Camper', 'vendor' => 'Tune Outdoor', 'description' => 'Hard side aluminium camper. Stand up room, big side window and it stays put on washboard.', 'install_date' => '2024-06-28', 'cost' => 1850000, 'url' => 'https://tuneoutdoor.com', 'timeline' => true, 'layer' => 'camper', 'hotspot' => [19.6, 38.8]],
            ['name' => 'Camper Side Door', 'vendor' => 'Tune Outdoor', 'description' => 'Door on the passenger side so camp faces away from the road.', 'install_date' => '2024-06-28', 'cost' => 0, 'timeline' => false, 'layer' => 'camper', 'hotspot' => [27.5, 30.8]],
            ['name' => 'Dometic CFX3 45', 'vendor' => 'Dometic', 'description' => 'Dual zone fridge freezer under the sleeping platform.', 'install_date' => '2024-07-02', 'cost' => 89900, 'timeline' => true, 'layer' => 'interior', 'hotspot' => [21.2, 40.6]],
            ['name' => 'Galley Drawer System', 'vendor' => 'Goose Gear', 'description' => 'Three drawers I built out for the stove, the pots and the dry food.', 'install_date' => '2024-08-15', 'cost' => 210000, 'timeline' => true, 'layer' => 'interior', 'hotspot' => [13.1, 42.3]],
            ['name' => 'Sleeping Platform', 'vendor' => null, 'description' => 'Plywood deck and a four inch foam mattress. Built it in a weekend.', 'install_date' => '2024-07-20', 'cost' => 38000, 'timeline' => false, 'layer' => 'interior', 'hotspot' => [19.6, 25.2]],
            ['name' => 'Redarc Dual Battery', 'vendor' => 'Redarc', 'description' => 'DC to DC charger and a 100Ah lithium under the galley.', 'install_date' => '2025-05-30', 'cost' => 142000, 'timeline' => true, 'layer' => 'interior', 'hotspot' => [27.9, 50.6]],
            ['name' => 'Fresh Water Tank', 'vendor' => null, 'description' => 'Twenty gallons plumbed to a foot pump at the galley.', 'install_date' => '2025-02-10', 'cost' => 46000, 'timeline' => false, 'layer' => 'interior', 'hotspot' => [27.9, 37.5]],
            ['name' => 'C4 Fabrication Front Bumper', 'vendor' => 'C4 Fabrication', 'description' => 'Hybrid bumper with a recovery point at each corner.', 'install_date' => '2025-03-15', 'cost' => 165000, 'timeline' => false, 'layer' => 'body', 'hotspot' => [90.2, 65.8]],
            ['name' => 'CBI Rock Sliders', 'vendor' => 'CBI Offroad', 'description' => 'Bolt on steel, and the reason the rockers are still straight.', 'install_date' => '2024-11-08', 'cost' => 98000, 'timeline' => false, 'layer' => 'underside', 'hotspot' => [51.7, 75.8]],
            ['name' => 'Old Man Emu Suspension', 'vendor' => 'ARB', 'description' => 'Two inch lift with heavy rear leaves for the camper weight.', 'install_date' => '2025-01-24', 'cost' => 189500, 'timeline' => true, 'layer' => 'underside', 'hotspot' => [26.9, 69.6]],
            ['name' => 'ARB Twin Compressor', 'vendor' => 'ARB', 'description' => 'Airs all four tyres back up in about six minutes.', 'install_date' => '2024-09-19', 'cost' => 55000, 'timeline' => true, 'layer' => 'underside', 'hotspot' => [76.6, 78.3]],
            ['name' => 'BFGoodrich KO2 285/75R16', 'vendor' => 'BFGoodrich', 'description' => 'Thirty three inch all terrains. Quiet enough on pavement, honest in the rocks.', 'install_date' => '2024-10-02', 'cost' => 132000, 'timeline' => false, 'layer' => 'underside', 'hotspot' => [76.4, 60.4]],
        ];
    }

    /**
     * Extra photographs for the homepage gallery, beyond the trip heroes.
     *
     * @return array<int, array{key: string, caption: string}>
     */
    public static function galleryImages(): array
    {
        return [
            ['key' => 'gallery-dunes', 'caption' => 'Dunes at last light'],
            ['key' => 'gallery-switchbacks', 'caption' => 'Switchbacks above the valley'],
            ['key' => 'gallery-camp-fire', 'caption' => 'Camp, somewhere off the rim'],
            ['key' => 'gallery-storm', 'caption' => 'Storm building over the pass'],
        ];
    }

    /**
     * Every image key the seeder generates - trip heroes, recipe heroes and
     * gallery extras. Cleanup uses this to find the files it created.
     *
     * @return array<int, string>
     */
    public static function imageKeys(): array
    {
        return [
            ...array_column(static::trips(), 'image'),
            ...array_column(static::recipes(), 'image'),
            ...array_column(static::galleryImages(), 'key'),
        ];
    }
}
