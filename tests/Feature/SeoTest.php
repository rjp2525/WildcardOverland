<?php

namespace Tests\Feature;

use App\Enums\MealType;
use App\Models\Recipe;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The head has to be right in the first response: the things that read it
 * are crawlers and link unfurlers, and none of them run JavaScript.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function trip(): Trip
    {
        return Trip::create([
            'name' => 'Alvord Desert Crossing',
            'slug' => 'alvord-desert-crossing',
            'summary' => 'Hot springs, a playa you can drive across at speed and not another vehicle for a day.',
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);
    }

    protected function recipe(): Recipe
    {
        $recipe = Recipe::create([
            'name' => 'Dutch Oven Chili',
            'slug' => 'dutch-oven-chili',
            'summary' => 'The one that gets made on almost every trip.',
            'meal_type' => MealType::Dinner,
            'prep_minutes' => 15,
            'cook_minutes' => 45,
            'servings' => 4,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $recipe->ingredients()->create(['order' => 0, 'quantity' => '1', 'unit' => 'lb', 'item' => 'ground beef']);
        $recipe->steps()->create(['order' => 0, 'body' => 'Brown the beef over a good bed of coals.']);

        return $recipe;
    }

    public function test_every_public_page_carries_a_unique_title_and_description(): void
    {
        $seen = [];

        foreach ([route('homepage'), route('about'), route('rig'), route('trips.index'), route('recipes.index')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match('/<title inertia>(.*?)<\/title>/', $html, $title);
            preg_match('/<meta name="description" content="(.*?)">/', $html, $description);

            $this->assertNotEmpty($title[1] ?? '', "No title on {$url}");
            $this->assertNotEmpty($description[1] ?? '', "No description on {$url}");
            $this->assertNotContains($title[1], $seen, "Duplicate title on {$url}");

            $seen[] = $title[1];
        }
    }

    public function test_a_trip_page_carries_its_own_card_and_canonical(): void
    {
        $trip = $this->trip();

        $this->get(route('trips.show', $trip->slug))
            ->assertSee('<title inertia>Alvord Desert Crossing | Wildcard Overland</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('trips.show', $trip->slug).'">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('Hot springs, a playa you can drive across', false);
    }

    public function test_a_recipe_page_publishes_recipe_structured_data(): void
    {
        $recipe = $this->recipe();

        $html = $this->get(route('recipes.show', $recipe->slug))->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $found);
        $graph = json_decode($found[1] ?? '{}', true);

        $types = array_column($graph['@graph'] ?? [], '@type');
        $this->assertSame(['WebSite', 'Person', 'Recipe', 'BreadcrumbList'], $types);

        $schema = $graph['@graph'][2];
        $this->assertSame('Dutch Oven Chili', $schema['name']);
        $this->assertSame('PT15M', $schema['prepTime']);
        $this->assertSame('PT60M', $schema['totalTime']);
        $this->assertSame('4 servings', $schema['recipeYield']);
        $this->assertSame(['1 lb ground beef'], $schema['recipeIngredient']);
        $this->assertSame('Brown the beef over a good bed of coals.', $schema['recipeInstructions'][0]['text']);
    }

    public function test_error_pages_are_kept_out_of_the_index(): void
    {
        $this->get('/no-such-trail')
            ->assertNotFound()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_the_sitemap_lists_published_pages_only(): void
    {
        $trip = $this->trip();
        Trip::create(['name' => 'Draft', 'slug' => 'draft', 'is_draft' => true]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('trips.show', $trip->slug))
            ->assertDontSee(route('trips.show', 'draft'));
    }

    public function test_robots_points_at_the_sitemap_and_shuts_out_the_admin(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_a_description_is_never_left_dangling_mid_word(): void
    {
        $trip = Trip::create([
            'name' => 'Long one',
            'slug' => 'long-one',
            'summary' => str_repeat('a very long summary that keeps going ', 20),
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        preg_match(
            '/<meta name="description" content="(.*?)">/',
            $this->get(route('trips.show', $trip->slug))->getContent(),
            $found,
        );

        $this->assertLessThanOrEqual(170, strlen($found[1]));
        $this->assertStringEndsWith('...', $found[1]);
    }
}
