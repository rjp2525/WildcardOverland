<?php

namespace App\Observers;

use App\Jobs\ReverseGeocodeCampsite;
use App\Models\Campsite;

class CampsiteObserver
{
    public function created(Campsite $campsite): void
    {
        $this->geocodeIfNeeded($campsite);
    }

    public function updated(Campsite $campsite): void
    {
        // Only when the coordinates actually moved, or nothing has resolved
        // them yet - editing a campsite's notes should not hit the network.
        if ($campsite->wasChanged(['latitude', 'longitude']) || $campsite->needsGeocoding()) {
            $this->geocodeIfNeeded($campsite);
        }
    }

    protected function geocodeIfNeeded(Campsite $campsite): void
    {
        if (config('geocoding.enabled') && $campsite->hasCoordinates()) {
            ReverseGeocodeCampsite::dispatch($campsite);
        }
    }
}
