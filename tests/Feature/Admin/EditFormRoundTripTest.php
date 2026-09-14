<?php

namespace Tests\Feature\Admin;

use App\Http\Requests\Admin\BrandRequest;
use App\Http\Requests\Admin\RecipeRequest;
use App\Http\Requests\Admin\TripRequest;
use App\Http\Requests\Admin\VehicleModificationRequest;
use App\Models\Brand;
use App\Models\Recipe;
use App\Models\Trip;
use App\Models\User;
use App\Models\VehicleModification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every field an edit form can submit has to come back from the edit screen.
 *
 * This is not tidiness. An admin form posts all of its fields at once, so a
 * field the screen does not hand back starts empty, and saving anything at
 * all then writes that emptiness over whatever was there. It reads as "the
 * create form did not save my checkboxes" and it is actually the edit screen
 * quietly wiping them on the next save.
 *
 * That is exactly what happened to the cooking methods.
 */
class EditFormRoundTripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /**
     * The top level fields a request accepts. Nested rules like "steps.*.body"
     * belong to a collection that is already covered by its own key.
     *
     * @return array<int, string>
     */
    protected function submittableFields(FormRequest $request): array
    {
        $keys = array_keys($request->rules());

        return array_values(array_unique(array_filter(
            $keys,
            fn (string $key) => ! str_contains($key, '.'),
        )));
    }

    /**
     * @param  array<int, string>  $fields
     */
    protected function assertRoundTrips(string $route, mixed $model, string $prop, array $fields): void
    {
        $page = $this->get($route)->assertOk()->viewData('page');
        $payload = $page['props'][$prop] ?? null;

        $this->assertIsArray($payload, "The edit screen sent no {$prop}.");

        $missing = array_values(array_filter(
            $fields,
            fn (string $field) => ! array_key_exists($field, $payload),
        ));

        $this->assertSame([], $missing, implode("\n", [
            'These can be submitted but are not sent back, so opening the form',
            'and saving would write over them:',
            ...$missing,
        ]));
    }

    public function test_a_recipe_round_trips_every_field(): void
    {
        $recipe = Recipe::create(['name' => 'Fried rice', 'slug' => 'fried-rice']);

        $this->assertRoundTrips(
            route('admin.recipes.edit', $recipe),
            $recipe,
            'recipe',
            $this->submittableFields(new RecipeRequest),
        );
    }

    public function test_a_trip_round_trips_every_field(): void
    {
        $trip = Trip::create(['name' => 'Baja', 'slug' => 'baja']);

        $this->assertRoundTrips(
            route('admin.trips.edit', $trip),
            $trip,
            'trip',
            $this->submittableFields(new TripRequest),
        );
    }

    public function test_a_brand_round_trips_every_field(): void
    {
        $brand = Brand::create(['name' => 'Tune Outdoor']);

        $this->assertRoundTrips(
            route('admin.brands.edit', $brand),
            $brand,
            'brand',
            $this->submittableFields(new BrandRequest),
        );
    }

    public function test_a_modification_round_trips_every_field(): void
    {
        $mod = VehicleModification::create(['name' => 'Rock sliders', 'category' => 'body']);

        $this->assertRoundTrips(
            route('admin.vehicle-modifications.edit', $mod),
            $mod,
            'modification',
            $this->submittableFields(new VehicleModificationRequest),
        );
    }
}
