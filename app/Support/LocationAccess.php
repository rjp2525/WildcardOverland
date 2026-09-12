<?php

namespace App\Support;

use App\Models\Campsite;
use Illuminate\Http\Request;

/**
 * Decides who may see exactly where a campsite is.
 *
 * Precise coordinates are the thing worth paying for - and worth protecting,
 * since a published pin is how a quiet spot stops being quiet. Everyone else
 * gets a deliberately fuzzed position: close enough to show the region on a
 * map, nowhere near precise enough to navigate to.
 *
 * There is one check here so that wiring a real subscription later is a change
 * to `granted()` alone, not a hunt through controllers.
 */
class LocationAccess
{
    /**
     * Roughly 8km of jitter. Enough to protect a site, small enough that the
     * pin still lands in the right valley.
     */
    protected const FUZZ_DEGREES = 0.075;

    public static function granted(?Request $request = null): bool
    {
        $user = ($request ?? request())->user();

        if ($user === null) {
            return false;
        }

        /*
         * Today the only accounts are admins, so signing in is the whole
         * check. When subscriptions land this becomes something like
         * `$user->subscribed('locations')` - and nothing else has to change.
         */
        return true;
    }

    /**
     * A campsite's position as the current viewer is allowed to see it.
     *
     * The offset is derived from the campsite id, so a given site always
     * lands in the same wrong place rather than jumping around between page
     * loads - which would both look broken and, over enough reloads, average
     * out to the real location.
     *
     * @return array{lat: float, lng: float, precise: bool}
     */
    public static function position(Campsite $campsite, ?Request $request = null): array
    {
        $lat = (float) $campsite->latitude;
        $lng = (float) $campsite->longitude;

        if (static::granted($request)) {
            return ['lat' => $lat, 'lng' => $lng, 'precise' => true];
        }

        // Two uncorrelated but stable offsets in [-1, 1].
        $seed = crc32('campsite:'.$campsite->id);
        $latOffset = (($seed % 2001) - 1000) / 1000;
        $lngOffset = ((intdiv($seed, 2003) % 2001) - 1000) / 1000;

        return [
            'lat' => round($lat + $latOffset * static::FUZZ_DEGREES, 4),
            'lng' => round($lng + $lngOffset * static::FUZZ_DEGREES, 4),
            'precise' => false,
        ];
    }
}
