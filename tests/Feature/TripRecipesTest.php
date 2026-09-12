<?php

namespace Tests\Feature;

use App\Enums\MealType;
use App\Models\Recipe;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripRecipesTest extends TestCase
{
    use RefreshDatabase;

    protected function trip(): Trip
    {
        return Trip::create([
            'name' => 'Mojave Road', 'slug' => 'mojave-road',
            'is_draft' => false, 'published_at' => now()->subDay(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function recipe(string $name, array $attributes = []): Recipe
    {
        return Recipe::create([
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'meal_type' => MealType::Dinner,
            'is_draft' => false,
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    public function test_a_trip_shows_the_recipes_cooked_on_it_in_order(): void
    {
        $trip = $this->trip();
        $chili = $this->recipe('Dutch Oven Chili');
        $bread = $this->recipe('Skillet Cornbread');

        $trip->recipes()->attach([
            $bread->id => ['order' => 1],
            $chili->id => ['order' => 0],
        ]);

        $this->get(route('trips.show', $trip->slug))
            ->assertInertia(fn ($page) => $page
                ->has('trip.recipes', 2)
                ->where('trip.recipes.0.name', 'Dutch Oven Chili')
                ->where('trip.recipes.1.name', 'Skillet Cornbread'));
    }

    public function test_a_trip_with_no_recipes_reports_an_empty_list(): void
    {
        $this->get(route('trips.show', $this->trip()->slug))
            ->assertInertia(fn ($page) => $page->has('trip.recipes', 0));
    }

    public function test_a_draft_recipe_is_not_shown_on_a_trip(): void
    {
        $trip = $this->trip();
        $trip->recipes()->attach($this->recipe('Live one')->id, ['order' => 0]);
        $trip->recipes()->attach($this->recipe('Still cooking', ['is_draft' => true])->id, ['order' => 1]);

        $this->get(route('trips.show', $trip->slug))
            ->assertInertia(fn ($page) => $page
                ->has('trip.recipes', 1)
                ->where('trip.recipes.0.name', 'Live one'));
    }

    public function test_a_recipe_can_belong_to_several_trips(): void
    {
        $chili = $this->recipe('Dutch Oven Chili');

        $first = $this->trip();
        $second = Trip::create([
            'name' => 'Alpine Loop', 'slug' => 'alpine-loop',
            'is_draft' => false, 'published_at' => now()->subDay(),
        ]);

        $first->recipes()->attach($chili->id, ['order' => 0]);
        $second->recipes()->attach($chili->id, ['order' => 0]);

        $this->assertSame(2, $chili->trips()->count());
    }
}
