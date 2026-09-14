<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Two questions a person answers without noticing and a script gets wrong.
 *
 * A field nobody can see, which a form filler will helpfully complete, and
 * an encrypted stamp of when the form was handed out. A person takes a few
 * seconds to write something; a script posts the instant it parses the page,
 * or replays the same form for a week.
 *
 * Chosen over a CAPTCHA on purpose. This costs the reader nothing, asks
 * nobody to identify traffic lights, sends no one to a third party and
 * leaves no one out. If real spam ever gets past it, a CAPTCHA can go in
 * front of this rather than instead of it.
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

    /** Handed to the form so the stamp cannot be forged or reused elsewhere. */
    public static function stamp(): string
    {
        return Crypt::encryptString((string) time());
    }

    /** The trap has to be empty and the stamp has to be a plausible age. */
    public static function passes(mixed $trap, mixed $stamp): bool
    {
        if (filled($trap)) {
            return false;
        }

        if (! is_string($stamp)) {
            return false;
        }

        try {
            $issued = (int) Crypt::decryptString($stamp);
        } catch (DecryptException) {
            return false;
        }

        $age = time() - $issued;

        return $age >= static::MIN_SECONDS && $age <= static::MAX_SECONDS;
    }
}
