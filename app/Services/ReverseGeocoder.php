<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves coordinates to a region (state / province) using OpenStreetMap's
 * Nominatim service - the same project whose tiles the campsite map uses, so
 * no extra account or API key.
 *
 * Nominatim's usage policy requires an identifying User-Agent and at most one
 * request per second. Callers are responsible for the rate limit; this class
 * just makes one well-behaved request and never throws, because a failed
 * lookup must not block saving a campsite.
 */
class ReverseGeocoder
{
    public const ENDPOINT = 'https://nominatim.openstreetmap.org/reverse';

    /**
     * @return array{state: string|null, country_code: string|null}|null
     *                                                                   null when the lookup could not be completed at all
     */
    public function region(float $latitude, float $longitude): ?array
    {
        try {
            $response = Http::withHeaders([
                // Required by the Nominatim usage policy.
                'User-Agent' => config('app.name').' ('.config('app.url').')',
            ])
                ->timeout(config('geocoding.timeout'))
                ->get(static::ENDPOINT, [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'jsonv2',
                    // 5 = state level; enough detail without a full address.
                    'zoom' => 5,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                Log::warning('Reverse geocode failed', [
                    'status' => $response->status(),
                    'lat' => $latitude,
                    'lon' => $longitude,
                ]);

                return null;
            }

            $address = $response->json('address') ?? [];

            return [
                // Nominatim uses `state` for most countries, but smaller ones
                // report a region or province instead.
                'state' => $address['state']
                    ?? $address['province']
                    ?? $address['region']
                    ?? null,
                'country_code' => isset($address['country_code'])
                    ? strtoupper($address['country_code'])
                    : null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Reverse geocode errored', [
                'message' => $e->getMessage(),
                'lat' => $latitude,
                'lon' => $longitude,
            ]);

            return null;
        }
    }
}
