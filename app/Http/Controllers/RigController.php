<?php

namespace App\Http\Controllers;

use App\Enums\BuildLayer;
use App\Models\VehicleModification;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The rig, part by part.
 *
 * Every modification is listed; the ones that have been given a layer and a
 * position also appear as a hotspot on the illustration, so an unplaced part
 * degrades to a list entry rather than disappearing.
 */
class RigController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $modifications = VehicleModification::query()
            ->orderByRaw('install_date IS NULL')
            ->orderByDesc('install_date')
            ->orderBy('name')
            ->get();

        return Inertia::render('Rig', [
            'seo' => Seo::make(
                title: 'The Rig',
                description: 'Every part on the Tacoma and where it sits, from the Tune M1L camper down to the sliders. Pull the whole thing apart and see what it cost.',
                card: route('og.card', ['kind' => 'page', 'slug' => 'rig']),
            ),
            'layers' => array_map(fn (BuildLayer $layer) => [
                'value' => $layer->value,
                'label' => $layer->label(),
                'depth' => $layer->depth(),
            ], BuildLayer::cases()),
            'parts' => $modifications->map(fn (VehicleModification $mod) => [
                'id' => $mod->id,
                'name' => $mod->name,
                'vendor' => $mod->vendor ?: $mod->purchased_from,
                'description' => $mod->description,
                'installed_label' => $mod->install_date
                    ? date('M Y', strtotime((string) $mod->install_date))
                    : null,
                'layer' => $mod->build_layer?->value,
                // Percentages of the illustration box, so the markup does not
                // need to know anything about the artwork's dimensions.
                'hotspot' => $mod->isPlaced()
                    ? ['x' => $mod->hotspot_x, 'y' => $mod->hotspot_y]
                    : null,
                'buyUrl' => $mod->buyUrl(),
                'isAffiliate' => (bool) $mod->affiliate_url,
            ])->all(),
            'stats' => [
                'parts' => $modifications->count(),
                'years' => $this->yearsBuilding($modifications),
            ],
        ]);
    }

    /**
     * Whole years between the first install and today - "three years in the
     * making" reads better than a part count alone.
     *
     * @param  Collection<int, VehicleModification>  $modifications
     */
    protected function yearsBuilding($modifications): int
    {
        $first = $modifications->pluck('install_date')->filter()->min();

        return $first === null
            ? 0
            : (int) floor((time() - strtotime((string) $first)) / (365.25 * 86400));
    }
}
