<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Three questions a person answers without noticing and a script gets wrong.
 *
 * A field nobody can see, which a form filler will helpfully complete. An
 * encrypted stamp of when the form was handed out, so a submission that
 * comes back faster than anybody could type is refused. And a nonce inside
 * that stamp which is spent the first time it is used, so one page fetched
 * once cannot be posted from a thousand times.
 *
 * The stamp also says which form it belongs to. A stamp handed out with the
 * rating widget is not a stamp for the comment box, and neither is a stamp
 * for some other site's form.
 *
 * Chosen over a CAPTCHA on purpose. This costs the reader nothing, asks
 * nobody to identify traffic lights, sends no one to a third party and
 * leaves no one out. It is not what stops somebody determined - nothing on
 * this side of the wire is - which is why it is one layer of several and
 * why nothing it lets through is published unexamined.
 */
class Honeypot
{
    /** Named like something a form filler wants to fill in. */
    public const FIELD = 'website';

    public const STAMP = 'filled_in';

    /** Faster than this and nobody typed it. */
    public const MIN_SECONDS = 3;

    /** A form older than this is a replay, not a slow reader. */
    public const MAX_SECONDS = 60 * 60 * 12;

    /** Where a spent nonce is remembered until it could not be replayed anyway. */
    protected const SPENT = 'honeypot:spent:';

    /**
     * Handed to one form, good for one submission.
     *
     * @param  string  $purpose  Which form this belongs to.
     */
    public static function stamp(string $purpose): string
    {
        return Crypt::encryptString(json_encode([
            't' => now()->getTimestamp(),
            'p' => $purpose,
            'n' => Str::random(24),
        ]));
    }

    /**
     * The trap has to be empty, the stamp has to be a plausible age, it has
     * to have come from this form, and it has to not have been used yet.
     *
     * Spends the nonce as a side effect, so calling this twice on the same
     * stamp fails the second time. That is the point of it.
     */
    public static function passes(mixed $trap, mixed $stamp, string $purpose): bool
    {
        if (filled($trap) || ! is_string($stamp)) {
            return false;
        }

        try {
            $payload = json_decode(Crypt::decryptString($stamp), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return false;
        }

        if (! is_array($payload) || ($payload['p'] ?? null) !== $purpose) {
            return false;
        }

        $age = now()->getTimestamp() - (int) ($payload['t'] ?? 0);

        if ($age < static::MIN_SECONDS || $age > static::MAX_SECONDS) {
            return false;
        }

        $nonce = $payload['n'] ?? null;

        if (! is_string($nonce) || $nonce === '') {
            return false;
        }

        /*
         * Remembered only as long as the stamp could have been replayed, and
         * add() is what makes this safe when two requests arrive together:
         * whichever writes the key first is the one that gets through.
         */
        return Cache::add(
            static::SPENT.hash('sha256', $nonce),
            true,
            now()->addSeconds(static::MAX_SECONDS),
        );
    }
}
