<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Trip;
use App\Models\VehicleModification;
use App\Support\AssetUrl;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('About', [
            'stats' => $this->stats(),
            'partners' => $this->partners(),
            'timeline' => $this->timeline(),
        ]);
    }

    /**
     * Counters backed by real records. Both derive from the same published
     * set, so the trip count and the nights total stay consistent with each
     * other - and with what a visitor could actually browse.
     *
     * The remaining figures in the Statistics component (miles, photos,
     * states, friends) have no source yet and stay hard-coded there.
     *
     * @return array<string, int>
     */
    protected function stats(): array
    {
        return [
            'trips' => Trip::published()->count(),
            'nights' => (int) Trip::published()->sum('calculated_nights'),
        ];
    }

    /**
     * Brands shown in the partner marquee. A brand needs a logo to appear,
     * and images flagged private are never rendered publicly.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function partners(): array
    {
        return Brand::query()
            ->whereHas('logo', fn ($query) => $query->where('private', false)->whereHas('file'))
            ->orderBy('name')
            ->get()
            ->map(fn (Brand $brand) => [
                'name' => $brand->name,
                'url' => $brand->website,
                'logo' => AssetUrl::image($brand->logo->file, 300, 146),
                'width' => $brand->logo->width,
                'height' => $brand->logo->height,
            ])
            ->all();
    }

    /**
     * Build modifications the admin has opted into showing, newest first.
     * Entries without an install date sort to the end.
     *
     * Cost is deliberately not exposed publicly.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function timeline(): array
    {
        return VehicleModification::query()
            ->where('shown_on_timeline', true)
            ->orderByRaw('install_date IS NULL')
            ->orderByDesc('install_date')
            ->orderBy('name')
            ->get()
            ->map(fn (VehicleModification $mod) => [
                'id' => $mod->id,
                'name' => $mod->name,
                'description' => $mod->description,
                'vendor' => $mod->vendor ?: $mod->purchased_from,
                'url' => $mod->url,
                'installed_on' => $mod->install_date,
                'installed_label' => $mod->install_date
                    ? date('M Y', strtotime((string) $mod->install_date))
                    : null,
            ])
            ->all();
    }
}
