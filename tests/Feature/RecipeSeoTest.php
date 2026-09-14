<?php

namespace Tests\Feature;

use App\Enums\ImageType;
use App\Models\File;
use App\Models\Image;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * What a crawler and a link unfurler get from a recipe page.
 *
 * All of it has to describe something actually on the page. Marking up a
 * claim the page does not make is how a rich result is lost along with the
 * trust that goes with it.
 */
class RecipeSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'assets-test']);
        Storage::fake('assets-test');
    }

    protected function image(string $name): Image
    {
        $file = File::create([
            'name' => $name, 'original_filename' => "{$name}.jpg", 'original_extension' => 'jpg',
            'mime' => 'image/jpeg', 'hash' => hash('sha256', $name), 'type' => 'content',
            'size' => 100, 'stored_path' => "uploads/{$name}.jpg", 'disk' => 'assets-test',
        ]);

        return Image::create([
            'name' => $name, 'type' => ImageType::Photo, 'file_id' => $file->id,
            'width' => 1600, 'height' => 900, 'private' => false,
        ]);
    }

    protected function recipe(array $attributes = []): Recipe
    {
        return Recipe::create([
            'name' => 'Skottle steak fried rice',
            'slug' => 'skottle-steak-fried-rice',
            'summary' => 'Big, crispy fried rice made on the Skottle.',
            'meal_type' => 'dinner',
            'is_draft' => false,
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    protected function graph(string $slug): array
    {
        $html = $this->get(route('recipes.show', $slug))->assertOk()->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);

        return json_decode($m[1] ?? '{}', true) ?? [];
    }

    protected function recipeNode(string $slug): array
    {
        foreach ($this->graph($slug)['@graph'] ?? [] as $node) {
            if (($node['@type'] ?? null) === 'Recipe') {
                return $node;
            }
        }

        return [];
    }

    public function test_the_kit_is_marked_up_as_method_and_tool(): void
    {
        $recipe = $this->recipe(['cooking_methods' => ['skottle', 'campfire']]);

        $node = $this->recipeNode($recipe->slug);

        $this->assertSame('Skottle, Campfire', $node['cookingMethod']);
        $this->assertSame(['Skottle', 'Campfire'], array_column($node['tool'], 'name'));
    }

    public function test_only_the_dietary_tags_that_are_the_same_claim_become_diets(): void
    {
        // one-pot and make-ahead are about how it is cooked, not what is in
        // it, and dairy-free is a stricter claim than any schema.org diet.
        $recipe = $this->recipe(['dietary' => ['vegan', 'gluten-free', 'one-pot', 'dairy-free']]);

        $node = $this->recipeNode($recipe->slug);

        $this->assertSame([
            'https://schema.org/VeganDiet',
            'https://schema.org/GlutenFreeDiet',
        ], $node['suitableForDiet']);

        // They are still described, just as keywords rather than as a diet.
        $this->assertStringContainsString('One pot', $node['keywords']);
    }

    public function test_a_photograph_is_offered_at_several_shapes(): void
    {
        $recipe = $this->recipe(['hero_image_id' => $this->image('fried-rice')->id]);

        $images = $this->recipeNode($recipe->slug)['image'];

        $this->assertIsArray($images);
        $this->assertCount(3, $images);
        foreach ($images as $url) {
            $this->assertStringStartsWith('http', $url);
        }
    }

    public function test_a_recipe_with_no_photograph_claims_no_photograph(): void
    {
        $recipe = $this->recipe();

        /*
         * The drawn link card is a title over a background, not a picture of
         * the food, so it is not offered as one. A recipe with no hero photo
         * simply does not qualify for a recipe rich result, which is the
         * true answer and a reason to go and add a photo.
         */
        $this->assertArrayNotHasKey('image', $this->recipeNode($recipe->slug));
    }

    public function test_a_private_photograph_is_never_offered(): void
    {
        $image = $this->image('secret');
        $image->update(['private' => true]);

        $recipe = $this->recipe(['hero_image_id' => $image->id]);

        // Same answer the page gives: it is not shown, so it is not claimed.
        $this->assertArrayNotHasKey('image', $this->recipeNode($recipe->slug));
    }

    public function test_it_says_when_it_was_published_and_last_changed(): void
    {
        $recipe = $this->recipe();
        $node = $this->recipeNode($recipe->slug);

        $this->assertSame($recipe->published_at->toIso8601String(), $node['datePublished']);
        $this->assertSame($recipe->updated_at->toIso8601String(), $node['dateModified']);
        $this->assertSame(route('recipes.show', $recipe->slug), $node['mainEntityOfPage']);
    }

    public function test_the_link_card_carries_the_article_properties(): void
    {
        $recipe = $this->recipe([
            'cooking_methods' => ['skottle'],
            'dietary' => ['one-pot'],
        ]);

        $this->get(route('recipes.show', $recipe->slug))
            ->assertOk()
            ->assertSee('property="article:published_time"', escape: false)
            ->assertSee('property="article:modified_time"', escape: false)
            ->assertSee('<meta property="article:section" content="Dinner">', escape: false)
            ->assertSee('<meta property="article:tag" content="One pot">', escape: false)
            ->assertSee('<meta property="article:tag" content="Skottle">', escape: false)
            ->assertSee('<meta property="article:author" content="Reno">', escape: false);
    }

    public function test_a_page_that_is_not_an_article_has_no_article_properties(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertDontSee('article:published_time', escape: false);
    }

    public function test_the_sitemap_lists_a_recipe_with_its_photograph(): void
    {
        $recipe = $this->recipe(['hero_image_id' => $this->image('fried-rice')->id]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('sitemap-image/1.1', escape: false)
            ->assertSee('<image:title>'.$recipe->name.'</image:title>', escape: false)
            ->assertSee(route('recipes.show', $recipe->slug), escape: false);
    }

    public function test_the_index_pages_date_themselves_from_their_newest_entry(): void
    {
        $recipe = $this->recipe();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<lastmod>'.$recipe->updated_at->toDateString().'</lastmod>', escape: false);
    }

    public function test_a_draft_is_in_neither_the_sitemap_nor_the_index(): void
    {
        $draft = $this->recipe(['slug' => 'secret', 'is_draft' => true]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee(route('recipes.show', $draft->slug), escape: false);
    }

    public function test_a_listing_page_describes_what_it_lists(): void
    {
        // Distinct dates, so "newest first" is a real ordering rather than
        // whichever row happened to be written first.
        $this->recipe(['name' => 'Fried rice', 'slug' => 'fried-rice', 'published_at' => now()->subWeek()]);
        $this->recipe(['name' => 'Camp hash', 'slug' => 'camp-hash', 'published_at' => now()->subDay()]);

        $html = $this->get(route('recipes.index'))->assertOk()->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);
        $graph = json_decode($m[1] ?? '{}', true)['@graph'] ?? [];

        $list = collect($graph)->firstWhere('@type', 'ItemList');

        $this->assertNotNull($list, 'The listing page should carry an ItemList.');
        $this->assertSame(2, $list['numberOfItems']);
        $this->assertSame(
            [route('recipes.show', 'camp-hash'), route('recipes.show', 'fried-rice')],
            array_column($list['itemListElement'], 'url'),
        );
        $this->assertSame([1, 2], array_column($list['itemListElement'], 'position'));
    }

    public function test_an_empty_listing_claims_no_list(): void
    {
        $html = $this->get(route('recipes.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('ItemList', $html);
    }
}
