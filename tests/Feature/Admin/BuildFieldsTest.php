<?php

namespace Tests\Feature\Admin;

use App\Enums\BuildLayer;
use App\Enums\MealType;
use App\Models\Recipe;
use App\Models\Trip;
use App\Models\User;
use App\Models\VehicleModification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin side of the two things the rig and trip pages added: recipes
 * cooked on a trip, and where a part sits on the build illustration.
 */
class BuildFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    protected function recipe(string $name): Recipe
    {
        return Recipe::create([
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'meal_type' => MealType::Dinner,
        ]);
    }

    public function test_the_trip_form_offers_every_recipe(): void
    {
        $this->recipe('Dutch Oven Chili');

        $this->get(route('admin.trips.create'))
            ->assertInertia(fn ($page) => $page
                ->has('recipeOptions', 1)
                ->where('recipeOptions.0.label', 'Dutch Oven Chili'));
    }

    public function test_recipes_are_attached_to_a_trip_in_the_submitted_order(): void
    {
        $chili = $this->recipe('Dutch Oven Chili');
        $bread = $this->recipe('Skillet Cornbread');

        $this->post(route('admin.trips.store'), [
            'name' => 'Mojave Road',
            'is_draft' => true,
            'recipes' => [['id' => $bread->id], ['id' => $chili->id]],
        ])->assertRedirect();

        $trip = Trip::firstWhere('name', 'Mojave Road');

        $this->assertSame(
            ['Skillet Cornbread', 'Dutch Oven Chili'],
            $trip->recipes->pluck('name')->all(),
        );
    }

    public function test_editing_a_trip_replaces_its_recipes(): void
    {
        $chili = $this->recipe('Dutch Oven Chili');
        $bread = $this->recipe('Skillet Cornbread');

        $trip = Trip::create(['name' => 'Mojave Road', 'slug' => 'mojave-road']);
        $trip->recipes()->attach($chili->id, ['order' => 0]);

        $this->put(route('admin.trips.update', $trip), [
            'name' => 'Mojave Road',
            'is_draft' => true,
            'recipes' => [['id' => $bread->id]],
        ])->assertRedirect();

        $this->assertSame(['Skillet Cornbread'], $trip->fresh()->recipes->pluck('name')->all());
    }

    public function test_an_unknown_recipe_is_rejected(): void
    {
        $this->post(route('admin.trips.store'), [
            'name' => 'Mojave Road',
            'is_draft' => true,
            'recipes' => [['id' => 9999]],
        ])->assertSessionHasErrors('recipes.0.id');
    }

    public function test_the_recipe_form_offers_the_cooking_methods(): void
    {
        $this->get(route('admin.recipes.create'))
            ->assertInertia(fn ($page) => $page
                ->has('cookingMethods', 8)
                ->where('cookingMethods.0.value', 'skottle'));
    }

    public function test_cooking_methods_are_saved_and_validated(): void
    {
        $this->post(route('admin.recipes.store'), [
            'name' => 'Skottle Hash',
            'meal_type' => MealType::Breakfast->value,
            'is_draft' => true,
            'cooking_methods' => ['skottle', 'skillet'],
        ])->assertRedirect();

        $this->assertSame(
            ['skottle', 'skillet'],
            Recipe::firstWhere('name', 'Skottle Hash')->cooking_methods,
        );

        $this->post(route('admin.recipes.store'), [
            'name' => 'Microwave Hash',
            'meal_type' => MealType::Breakfast->value,
            'is_draft' => true,
            'cooking_methods' => ['microwave'],
        ])->assertSessionHasErrors('cooking_methods.0');
    }

    public function test_the_modification_form_offers_the_build_layers(): void
    {
        $this->get(route('admin.vehicle-modifications.create'))
            ->assertInertia(fn ($page) => $page
                ->has('buildLayers', 5)
                ->where('buildLayers.0.value', 'roof'));
    }

    public function test_a_part_can_be_placed_on_the_illustration(): void
    {
        $this->post(route('admin.vehicle-modifications.store'), [
            'name' => 'Prinsu Roof Rack',
            'shown_on_timeline' => true,
            'affiliate_url' => 'https://example.com/ref',
            'build_layer' => 'roof',
            'hotspot_x' => 58.6,
            'hotspot_y' => 15.4,
        ])->assertRedirect();

        $part = VehicleModification::firstWhere('name', 'Prinsu Roof Rack');

        $this->assertSame(BuildLayer::Roof, $part->build_layer);
        $this->assertSame(58.6, $part->hotspot_x);
        $this->assertSame(15.4, $part->hotspot_y);
        $this->assertSame('https://example.com/ref', $part->buyUrl());
    }

    public function test_a_hotspot_outside_the_illustration_is_rejected(): void
    {
        $this->post(route('admin.vehicle-modifications.store'), [
            'name' => 'Nowhere',
            'shown_on_timeline' => false,
            'hotspot_x' => 140,
            'hotspot_y' => -3,
        ])->assertSessionHasErrors(['hotspot_x', 'hotspot_y']);
    }

    public function test_an_unknown_build_layer_is_rejected(): void
    {
        $this->post(route('admin.vehicle-modifications.store'), [
            'name' => 'Nowhere',
            'shown_on_timeline' => false,
            'build_layer' => 'bonnet',
        ])->assertSessionHasErrors('build_layer');
    }

    public function test_the_edit_form_carries_the_stored_placement_back(): void
    {
        $part = VehicleModification::create([
            'name' => 'Prinsu Roof Rack',
            'shown_on_timeline' => true,
            'build_layer' => BuildLayer::Roof,
            'hotspot_x' => 58.6,
            'hotspot_y' => 15.4,
            'affiliate_url' => 'https://example.com/ref',
        ]);

        $this->get(route('admin.vehicle-modifications.edit', $part))
            ->assertInertia(fn ($page) => $page
                ->where('modification.build_layer', 'roof')
                ->where('modification.hotspot_x', 58.6)
                ->where('modification.hotspot_y', 15.4)
                ->where('modification.affiliate_url', 'https://example.com/ref'));
    }
}
