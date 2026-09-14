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
 * A plain list grouped by where each part lives. There was an exploded
 * drawing of the truck here; it was not good enough to keep, and a list you
 * can actually read beats a picture that is nearly right. The artwork and
 * the hotspot columns are still in the repository for when it comes back.
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
                description: 'Every part on the Tacoma and where it sits, from the Tune M1L camper down to the sliders, with what each one cost.',
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
                // Stored as integer cents, presented as whole dollars: nobody
                // needs to know a set of sliders was 899 dollars and 47 cents.
                'cost' => $mod->cost === null ? null : (int) round($mod->cost / 100),
                'buyUrl' => $mod->buyUrl(),
                'isAffiliate' => (bool) $mod->affiliate_url,
            ])->all(),
            'stats' => [
                'parts' => $modifications->count(),
                'years' => $this->yearsBuilding($modifications),
                /*
                 * What is actually known, not a guess at the whole build.
                 * Parts with no price recorded are counted so the number can
                 * be honest about being a floor rather than a total.
                 */
                'spend' => (int) round($modifications->sum('cost') / 100),
                'priced' => $modifications->whereNotNull('cost')->count(),
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
