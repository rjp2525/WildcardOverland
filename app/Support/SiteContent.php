<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Campsite;
use App\Models\Image;
use App\Models\Trip;
use App\Models\VehicleModification;

/**
 * Content shared by more than one public page, so the homepage and the About
 * page cannot drift apart on what they claim.
 */
class SiteContent
{
    /**
     * Counters backed by real records. Trips, nights and miles come from the
     * same published set so they stay consistent with each other; photos
     * counts images typed as photographs, excluding private ones.
     *
     * States is the number of distinct regions campsites resolved to. Those
     * are geocoded from coordinates, so a campsite whose lookup has not run
     * (or found nothing) simply does not contribute.
     *
     * @return array<string, int>
     */
    public static function stats(): array
    {
        return [
            'trips' => Trip::published()->count(),
            'nights' => (int) Trip::published()->sum('calculated_nights'),
            'miles' => (int) Trip::published()->sum('miles'),
            'photos' => Image::publicPhotos()->count(),
            'states' => Campsite::query()
                ->whereNotNull('state')
                ->whereHas('trip', fn ($query) => $query->published())
                ->distinct()
                ->count('state'),
        ];
    }

    /**
     * Brands shown in the partner marquee. A brand needs a logo to appear,
     * and images flagged private are never rendered publicly.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function partners(): array
    {
        return Brand::query()
            ->whereHas('logo', fn ($query) => $query->where('private', false)->whereHas('file'))
            ->orderBy('name')
            ->get()
            ->map(function (Brand $brand) {
                $logo = ImagePresenter::contain($brand->logo, alt: $brand->name);

                return $logo === null ? null : [
                    'name' => $brand->name,
                    'url' => $brand->website,
                    // The whole payload, so the marquee gets a srcset too.
                    'logo' => $logo,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Build modifications the admin has opted into showing, newest first,
     * undated last. Cost is deliberately not exposed publicly.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function timeline(?int $limit = null): array
    {
        return VehicleModification::query()
            ->where('shown_on_timeline', true)
            ->orderByRaw('install_date IS NULL')
            ->orderByDesc('install_date')
            ->orderBy('name')
            ->when($limit, fn ($query) => $query->take($limit))
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

    /**
     * Curated photographs for the homepage gallery.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function gallery(int $limit = 8): array
    {
        return Image::featured()
            ->with('file')
            ->take($limit)
            ->get()
            ->map(fn (Image $image) => ImagePresenter::thumb($image))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Campsites from published trips, for the map.
     *
     * Positions run through LocationAccess, so a public visitor gets fuzzed
     * pins rather than exact ones - otherwise the map would hand out the very
     * coordinates the trip page withholds.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function campsitePoints(): array
    {
        return Campsite::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('trip', fn ($query) => $query->published())
            ->with('trip:id,name,slug')
            ->orderBy('trip_id')
            ->orderBy('order')
            ->get()
            ->map(function (Campsite $campsite) {
                $position = LocationAccess::position($campsite);

                return [
                    'name' => $campsite->name,
                    'nights' => $campsite->nights,
                    'lat' => $position['lat'],
                    'lng' => $position['lng'],
                    'precise' => $position['precise'],
                    'state' => $campsite->state,
                    'trip' => $campsite->trip?->name,
                    'url' => $campsite->trip ? route('trips.show', $campsite->trip->slug) : null,
                ];
            })
            ->all();
    }

    /**
     * Where the map should open: the tightest box around the densest cluster
     * of campsites, rather than everything at once. Trips spanning Oregon to
     * Baja would otherwise frame the whole continent.
     *
     * @param  array<int, array<string, mixed>>  $points
     * @return array{south: float, west: float, north: float, east: float}|null
     */
    public static function mapFocus(array $points): ?array
    {
        if ($points === []) {
            return null;
        }

        // Degrees; roughly a day's drive, so one region reads as one cluster.
        $radius = 6.0;

        $best = [];

        foreach ($points as $centre) {
            $cluster = array_values(array_filter(
                $points,
                fn (array $p) => abs($p['lat'] - $centre['lat']) <= $radius
                    && abs($p['lng'] - $centre['lng']) <= $radius,
            ));

            if (count($cluster) > count($best)) {
                $best = $cluster;
            }
        }

        $lats = array_column($best, 'lat');
        $lngs = array_column($best, 'lng');

        // A little breathing room so pins are not against the edge.
        $pad = 0.4;

        return [
            'south' => min($lats) - $pad,
            'west' => min($lngs) - $pad,
            'north' => max($lats) + $pad,
            'east' => max($lngs) + $pad,
        ];
    }
}
