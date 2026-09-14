<?php

namespace Tests\Feature;

use App\Enums\ImageType;
use App\Enums\MealType;
use App\Models\File;
use App\Models\Image;
use App\Models\Recipe;
use App\Models\Trip;
use App\Support\RichText\TipTap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'assets-test']);
        Storage::fake('assets-test');
        Storage::fake('local');
    }

    protected function image(string $name): Image
    {
        $file = File::create([
            'name' => $name, 'original_filename' => "{$name}.png", 'original_extension' => 'png',
            'mime' => 'image/png', 'hash' => hash('sha256', $name), 'type' => 'content',
            'size' => 100, 'stored_path' => "uploads/{$name}.png", 'disk' => 'assets-test',
        ]);

        return Image::create([
            'name' => $name, 'type' => ImageType::Photo, 'file_id' => $file->id,
            'width' => 100, 'height' => 100,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function trip(string $name, array $attributes = []): Trip
    {
        return Trip::create([
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'is_draft' => false,
            'published_at' => now()->subDay(),
            ...$attributes,
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

    public function test_the_trips_index_lists_published_trips_newest_first(): void
    {
        $this->trip('Older', ['start_date' => '2026-01-01', 'end_date' => '2026-01-05']);
        $this->trip('Newer', ['start_date' => '2026-06-01', 'end_date' => '2026-06-04']);
        $this->trip('Draft', ['is_draft' => true]);

        $this->get(route('trips.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('trips/Index')
                ->has('trips.data', 2)
                ->where('trips.data.0.name', 'Newer')
                ->where('trips.data.1.name', 'Older'));
    }

    public function test_a_trip_page_renders_its_content_gallery_and_campsites(): void
    {
        $trip = $this->trip('Baja', [
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-09',
            'content' => '<p>The write-up.</p>',
            'hero_image_id' => $this->image('hero')->id,
        ]);
        $trip->images()->sync([$this->image('shot')->id => ['order' => 0, 'caption' => 'Camp']]);
        $trip->campsites()->create(['order' => 0, 'name' => 'Gonzaga Bay', 'nights' => 3]);

        $this->get(route('trips.show', $trip->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('trips/Show')
                ->where('trip.name', 'Baja')
                ->where('trip.nights', 8)
                ->where('trip.date_label', 'March 2026')
                ->has('trip.hero')
                ->has('trip.gallery', 1)
                ->where('trip.gallery.0.caption', 'Camp')
                ->has('trip.campsites', 1));
    }

    public function test_a_trip_spanning_months_says_so(): void
    {
        $trip = $this->trip('Long one', ['start_date' => '2026-03-20', 'end_date' => '2026-04-05']);

        $this->get(route('trips.show', $trip->slug))
            ->assertInertia(fn ($page) => $page->where('trip.date_label', 'March – April 2026'));
    }

    public function test_unpublished_trips_are_not_reachable(): void
    {
        $draft = $this->trip('Secret', ['is_draft' => true]);
        $scheduled = $this->trip('Scheduled', ['published_at' => now()->addWeek()]);

        $this->get(route('trips.show', $draft->slug))->assertNotFound();
        $this->get(route('trips.show', $scheduled->slug))->assertNotFound();
    }

    public function test_the_recipes_index_can_be_filtered_by_meal(): void
    {
        $this->recipe('Hash', ['meal_type' => MealType::Breakfast]);
        $this->recipe('Chili', ['meal_type' => MealType::Dinner]);

        $this->get(route('recipes.index'))
            ->assertInertia(fn ($page) => $page->has('recipes.data', 2));

        $this->get(route('recipes.index', ['meal' => 'breakfast']))
            ->assertInertia(fn ($page) => $page
                ->has('recipes.data', 1)
                ->where('recipes.data.0.name', 'Hash')
                ->where('activeMeal', 'breakfast'));
    }

    public function test_an_unknown_meal_filter_is_rejected(): void
    {
        $this->get(route('recipes.index', ['meal' => 'elevenses']))
            ->assertSessionHasErrors('meal');
    }

    public function test_a_recipe_page_renders_its_ingredients_and_steps(): void
    {
        $recipe = $this->recipe('Chili', ['prep_minutes' => 15, 'cook_minutes' => 45, 'servings' => 4]);
        $recipe->ingredients()->create(['order' => 0, 'quantity' => '1', 'unit' => 'lb', 'item' => 'beef']);
        $step = $recipe->steps()->create(['order' => 0, 'body' => TipTap::fromText('Brown the beef.')]);
        $step->tips()->create([
            'order' => 0,
            'kind' => 'warning',
            'body' => TipTap::fromText('Coals, not flame.'),
        ]);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('recipes/Show')
                ->where('recipe.total_minutes', 60)
                ->where('recipe.ingredients.0.label', '1 lb beef')
                ->where('recipe.steps.0.body', '<p>Brown the beef.</p>')
                ->where('recipe.steps.0.tips.0.body', '<p>Coals, not flame.</p>')
                ->where('recipe.steps.0.tips.0.kind', 'warning')
                ->where('recipe.steps.0.image', null));
    }

    public function test_unpublished_recipes_are_not_reachable(): void
    {
        $draft = $this->recipe('Secret', ['is_draft' => true]);

        $this->get(route('recipes.show', $draft->slug))->assertNotFound();
        $this->get(route('recipes.index'))
            ->assertInertia(fn ($page) => $page->has('recipes.data', 0));
    }

    public function test_a_private_image_is_never_presented(): void
    {
        $image = $this->image('secret');
        $image->update(['private' => true]);
        $trip = $this->trip('Baja', ['hero_image_id' => $image->id]);

        $this->get(route('trips.show', $trip->slug))
            ->assertInertia(fn ($page) => $page->where('trip.hero', null));
    }
}
