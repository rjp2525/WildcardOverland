<?php

namespace App\Jobs;

use App\Models\Campsite;
use App\Services\ReverseGeocoder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Fills in a campsite's state from its coordinates.
 *
 * Queued so saving a campsite in the admin never waits on a third-party
 * service. Under the `sync` queue driver it simply runs inline.
 */
class ReverseGeocodeCampsite implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public Campsite $campsite) {}

    public function handle(ReverseGeocoder $geocoder): void
    {
        if (! config('geocoding.enabled')) {
            return;
        }

        $campsite = $this->campsite->fresh();

        if ($campsite === null || ! $campsite->hasCoordinates()) {
            return;
        }

        $region = $geocoder->region((float) $campsite->latitude, (float) $campsite->longitude);

        if ($region === null) {
            // The lookup itself failed. Leave geocoded_at unset so a later
            // backfill retries this one.
            return;
        }

        /*
         * A lookup can succeed and still resolve no region - a point at sea,
         * for instance. Stamp it so we stop retrying, but never overwrite a
         * state that was set another way with a null.
         */
        $campsite->forceFill(array_filter([
            'state' => $region['state'],
            'country_code' => $region['country_code'],
        ], fn ($value) => $value !== null) + ['geocoded_at' => now()])
            ->saveQuietly();
    }
}
