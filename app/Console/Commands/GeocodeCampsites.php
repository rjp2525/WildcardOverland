<?php

namespace App\Console\Commands;

use App\Models\Campsite;
use App\Services\ReverseGeocoder;
use Illuminate\Console\Command;

class GeocodeCampsites extends Command
{
    protected $signature = 'campsites:geocode
                            {--force : Re-resolve campsites that already have a state}';

    protected $description = 'Resolve campsite coordinates to states, for the About page counter';

    public function handle(ReverseGeocoder $geocoder): int
    {
        if (! config('geocoding.enabled')) {
            $this->warn('Geocoding is disabled (GEOCODING_ENABLED=false). Nothing to do.');

            return self::SUCCESS;
        }

        $campsites = Campsite::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->unless($this->option('force'), fn ($query) => $query->whereNull('state'))
            ->get();

        if ($campsites->isEmpty()) {
            $this->info('Every campsite with coordinates already has a state.');

            return self::SUCCESS;
        }

        // Nominatim allows one request per second; going faster gets you blocked.
        $throttle = max(0, (int) config('geocoding.throttle_seconds'));
        $resolved = 0;
        $failed = 0;

        $this->withProgressBar($campsites, function (Campsite $campsite) use ($geocoder, $throttle, &$resolved, &$failed) {
            $region = $geocoder->region((float) $campsite->latitude, (float) $campsite->longitude);

            if ($region === null || $region['state'] === null) {
                $failed++;
            } else {
                $campsite->forceFill([
                    'state' => $region['state'],
                    'country_code' => $region['country_code'],
                    'geocoded_at' => now(),
                ])->saveQuietly();

                $resolved++;
            }

            if ($throttle > 0) {
                sleep($throttle);
            }
        });

        $this->newLine(2);
        $this->info("Resolved {$resolved} campsite(s).");

        if ($failed > 0) {
            $this->warn("{$failed} could not be resolved; re-run to retry them.");
        }

        return self::SUCCESS;
    }
}
