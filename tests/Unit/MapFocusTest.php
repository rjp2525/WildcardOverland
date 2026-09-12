<?php

namespace Tests\Unit;

use App\Support\SiteContent;
use PHPUnit\Framework\TestCase;

/**
 * The map opens on wherever most of the camping has happened, not on the
 * whole continent - a single trip to Baja should not zoom Oregon out of
 * sight of anyone looking at the map.
 */
class MapFocusTest extends TestCase
{
    /**
     * @param  array<int, array{float, float}>  $points
     * @return array<int, array<string, float>>
     */
    protected function points(array $points): array
    {
        return array_map(fn (array $p) => ['lat' => $p[0], 'lng' => $p[1]], $points);
    }

    public function test_there_is_nothing_to_focus_on_without_points(): void
    {
        $this->assertNull(SiteContent::mapFocus([]));
    }

    public function test_it_frames_the_densest_cluster_and_ignores_the_outlier(): void
    {
        $focus = SiteContent::mapFocus($this->points([
            [37.83, -107.70],  // Colorado
            [37.93, -107.57],
            [38.42, -109.80],  // Utah, same cluster
            [26.60, -111.80],  // Baja, a long way south
        ]));

        $this->assertNotNull($focus);
        $this->assertGreaterThan(30.0, $focus['south']);
        $this->assertLessThan(40.0, $focus['north']);
    }

    public function test_a_single_point_still_produces_a_box_around_it(): void
    {
        $focus = SiteContent::mapFocus($this->points([[42.54, -118.53]]));

        $this->assertNotNull($focus);
        $this->assertLessThan(42.54, $focus['south']);
        $this->assertGreaterThan(42.54, $focus['north']);
        $this->assertLessThan(-118.53, $focus['west']);
        $this->assertGreaterThan(-118.53, $focus['east']);
    }
}
