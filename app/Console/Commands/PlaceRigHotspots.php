<?php

namespace App\Console\Commands;

use App\Models\VehicleModification;
use Illuminate\Console\Command;

/**
 * Puts the build hotspots back where the parts now are.
 *
 * A hotspot is stored as a percentage of the illustration, so redrawing the
 * truck leaves every one of them pointing at the wrong panel. This carries
 * the positions for the parts the drawing was laid out around, so they do
 * not all have to be dragged into place by hand again.
 *
 * It matches on name and skips anything it does not recognise, so a part
 * placed by hand is only moved if it is one of these.
 */
class PlaceRigHotspots extends Command
{
    protected $signature = 'rig:place-hotspots {--dry-run : Show what would move without touching anything}';

    protected $description = 'Move the build hotspots onto the redrawn truck';

    /**
     * Percentages of the illustration, taken off the drawing rather than
     * guessed: the viewBox is 1000 wide and runs from y=10 to y=490.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    protected array $places = [
        // Roof, along the rack.
        'Prinsu Roof Rack' => [37.0, 10.4],
        'Renogy 200W Solar' => [33.5, 7.9],
        'Baja Designs Light Bar' => [46.8, 36.5],

        // Camper.
        'Tune M1L Camper' => [20.0, 43.8],
        '270 Degree Awning' => [40.0, 33.8],

        // Inside, along the galley.
        'Redarc Dual Battery' => [20.0, 50.0],
        'Galley Drawer System' => [22.2, 47.1],
        'Dometic CFX3 45' => [30.0, 49.4],
        'Fresh Water Tank' => [35.0, 50.4],
        'Sleeping Platform' => [30.0, 27.1],

        // Truck.
        'C4 Fabrication Front Bumper' => [79.0, 66.7],

        // Underneath.
        'Old Man Emu Suspension' => [34.4, 78.3],
        'BFGoodrich KO2 285/75R16' => [69.8, 81.3],
        'ARB Twin Compressor' => [52.0, 80.8],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $moved = 0;

        foreach (VehicleModification::all() as $part) {
            $place = $this->places[$part->name] ?? null;

            if ($place === null) {
                $this->line("  skipped  {$part->name}");

                continue;
            }

            [$x, $y] = $place;

            $this->line(sprintf(
                '  %s  %-30s %s -> %.1f, %.1f',
                $dry ? 'would move' : 'moved     ',
                $part->name,
                $part->isPlaced() ? sprintf('%.1f, %.1f', $part->hotspot_x, $part->hotspot_y) : 'unplaced',
                $x,
                $y,
            ));

            if (! $dry) {
                $part->update(['hotspot_x' => $x, 'hotspot_y' => $y]);
            }

            $moved++;
        }

        $this->newLine();
        $this->info($dry
            ? "{$moved} would move. Run it again without --dry-run to apply."
            : "{$moved} moved. Anything else is still where you put it.");

        return self::SUCCESS;
    }
}
