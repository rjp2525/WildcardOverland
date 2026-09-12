<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reverse Geocoding
    |--------------------------------------------------------------------------
    |
    | Campsite coordinates are resolved to a state/province so the About page
    | can count states visited. Set GEOCODING_ENABLED=false to skip lookups
    | entirely - campsites still save, they just carry no region.
    |
    */

    'enabled' => env('GEOCODING_ENABLED', true),

    'timeout' => (int) env('GEOCODING_TIMEOUT', 8),

    /*
    | Seconds to wait between requests when backfilling in bulk. Nominatim's
    | usage policy allows at most one request per second.
    */
    'throttle_seconds' => (int) env('GEOCODING_THROTTLE', 1),
];
