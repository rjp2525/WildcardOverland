<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Who someone is when nobody signs in.
 *
 * A random token in their own cookie, hashed with the app key before it is
 * stored. That is enough to recognise the same person coming back to change
 * a rating, and not enough to be a record of anybody: the stored value
 * cannot be turned back into the token, and the token means nothing anywhere
 * except this site.
 *
 * The address is hashed for the same reason. It is only ever used to notice
 * a hundred submissions from one place, never to identify anyone, and it is
 * deliberately never stored in a form that could be read back.
 */
class Visitor
{
    public const COOKIE = 'wo_visitor';

    /** Long enough to still be recognised next season. */
    public const LIFETIME_MINUTES = 60 * 24 * 400;

    /**
     * This visitor's stored identity, issuing them one if they arrived
     * without it. A freshly minted token is queued back to them so the next
     * visit is recognised as the same person.
     */
    public static function identify(Request $request): string
    {
        $token = $request->cookie(static::COOKIE);

        if (! is_string($token) || strlen($token) < 32) {
            $token = Str::random(40);

            Cookie::queue(Cookie::make(
                static::COOKIE,
                $token,
                static::LIFETIME_MINUTES,
                httpOnly: true,
                sameSite: 'lax',
            ));
        }

        return static::hash($token);
    }

    /**
     * Who they already are, without making them anybody.
     *
     * For rendering a page: it says whether this browser has rated before
     * without handing a cookie to every reader who only came to read. One
     * is only issued when somebody actually sends something in.
     */
    public static function existing(Request $request): ?string
    {
        $token = $request->cookie(static::COOKIE);

        return is_string($token) && strlen($token) >= 32 ? static::hash($token) : null;
    }

    public static function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    /** Never the address itself, only something that matches itself. */
    public static function addressHash(Request $request): ?string
    {
        $ip = $request->ip();

        return $ip === null ? null : static::hash('ip:'.$ip);
    }
}
