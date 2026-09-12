<?php

namespace App\Support;

/**
 * Signs and verifies asset URLs.
 *
 * The signature is what makes it safe to expose an image transformer to the
 * internet: without it, anyone could ask the server to render every variant
 * of every file until the disk filled. It covers the path and every
 * parameter, so no part of the request can be altered on its own.
 */
class AssetSignature
{
    /**
     * @param  array<string, scalar>  $params
     */
    public static function generate(string $path, array $params): string
    {
        return hash_hmac('sha256', static::payload($path, $params), (string) config('app.key'));
    }

    /**
     * @param  array<string, mixed>  $params  The whole request, signature included
     */
    public static function verify(string $path, array $params): bool
    {
        $provided = (string) ($params['s'] ?? '');

        unset($params['s']);

        // hash_equals: a plain === leaks how much of the signature matched.
        return hash_equals(static::generate($path, $params), $provided);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected static function payload(string $path, array $params): string
    {
        unset($params['s']);

        // Sorted, so a reordered query string is still the same request.
        ksort($params);

        return '/'.ltrim($path, '/').'?'.http_build_query($params);
    }
}
