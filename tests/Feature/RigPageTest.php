<?php

namespace Tests\Feature;

use App\Enums\BuildLayer;
use App\Models\VehicleModification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RigPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function part(string $name, array $attributes = []): VehicleModification
    {
        return VehicleModification::create([
            'name' => $name,
            'shown_on_timeline' => true,
            ...$attributes,
        ]);
    }

    public function test_the_page_renders_with_nothing_bolted_on_yet(): void
    {
        $this->get(route('rig'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Rig')
                ->has('parts', 0)
                ->has('layers', 5)
                ->where('stats.parts', 0)
                ->where('stats.years', 0));
    }

    public function test_a_placed_part_carries_its_layer_and_hotspot(): void
    {
        $this->part('Prinsu Roof Rack', [
            'vendor' => 'Prinsu',
            'build_layer' => BuildLayer::Roof,
            'hotspot_x' => 58.6,
            'hotspot_y' => 15.4,
            'install_date' => '2024-04-12',
        ]);

        $this->get(route('rig'))
            ->assertInertia(fn ($page) => $page
                ->where('parts.0.name', 'Prinsu Roof Rack')
                ->where('parts.0.vendor', 'Prinsu')
                ->where('parts.0.layer', 'roof')
                ->where('parts.0.hotspot.x', 58.6)
                ->where('parts.0.hotspot.y', 15.4)
                ->where('parts.0.installed_label', 'Apr 2024'));
    }

    public function test_an_unplaced_part_is_still_listed(): void
    {
        $this->part('A sticker', ['build_layer' => BuildLayer::Body]);

        $this->get(route('rig'))
            ->assertInertia(fn ($page) => $page
                ->has('parts', 1)
                ->where('parts.0.hotspot', null)
                ->where('parts.0.layer', 'body'));
    }

    public function test_a_part_needs_both_coordinates_to_be_placed(): void
    {
        $this->part('Half placed', ['build_layer' => BuildLayer::Body, 'hotspot_x' => 40]);

        $this->get(route('rig'))
            ->assertInertia(fn ($page) => $page->where('parts.0.hotspot', null));
    }

    public function test_the_buy_link_prefers_the_affiliate_url(): void
    {
        $this->part('Plain link', ['url' => 'https://example.com/plain']);
        $this->part('Affiliate link', [
            'url' => 'https://example.com/plain',
            'affiliate_url' => 'https://example.com/ref',
        ]);

        $this->get(route('rig'))
            ->assertInertia(function ($page) {
                $parts = collect($page->toArray()['props']['parts'])->keyBy('name');

                $this->assertSame('https://example.com/plain', $parts['Plain link']['buyUrl']);
                $this->assertFalse($parts['Plain link']['isAffiliate']);
                $this->assertSame('https://example.com/ref', $parts['Affiliate link']['buyUrl']);
                $this->assertTrue($parts['Affiliate link']['isAffiliate']);
            });
    }

    public function test_parts_are_ordered_newest_first_with_undated_ones_last(): void
    {
        $this->part('Undated');
        $this->part('Older', ['install_date' => '2024-01-01']);
        $this->part('Newer', ['install_date' => '2025-01-01']);

        $this->get(route('rig'))
            ->assertInertia(fn ($page) => $page
                ->where('parts.0.name', 'Newer')
                ->where('parts.1.name', 'Older')
                ->where('parts.2.name', 'Undated'));
    }

    public function test_the_years_counter_runs_from_the_first_install(): void
    {
        $this->part('First', ['install_date' => now()->subYears(3)->subMonth()->toDateString()]);
        $this->part('Latest', ['install_date' => now()->subMonth()->toDateString()]);

        $this->get(route('rig'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.parts', 2)
                ->where('stats.years', 3));
    }
}
