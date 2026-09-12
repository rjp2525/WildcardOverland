<?php

namespace App\Support;

/**
 * Error copy, written the way the rest of the site is written.
 *
 * A status code on a blank page tells a visitor nothing they can act on. The
 * job of these is to say what happened and what to do next, in the language
 * of the thing the site is about.
 */
class TrailStatus
{
    /**
     * @return array{code: int, sign: string, title: string, body: string, hint: string|null}
     */
    public static function for(int $status): array
    {
        $copy = static::table()[$status] ?? static::fallback($status);

        return ['code' => $status, ...$copy];
    }

    /**
     * @return array<int, array{sign: string, title: string, body: string, hint: string|null}>
     */
    protected static function table(): array
    {
        return [
            404 => [
                'sign' => 'Trail closed',
                'title' => "There's a tree down across this one",
                'body' => 'Whatever you came looking for is not here. Either it moved or I never cut this line in the first place. Either way you are not getting through without a chainsaw.',
                'hint' => 'Back it up and pick another route.',
            ],
            403 => [
                'sign' => 'Gate locked',
                'title' => 'This one runs through private land',
                'body' => 'There is a gate across the road and I do not have a key to hand out. Nothing personal, some of these spots stay quiet for a reason.',
                'hint' => 'Try a route that is open to everyone.',
            ],
            401 => [
                'sign' => 'Check in first',
                'title' => 'The ranger wants to see a permit',
                'body' => 'You need to be signed in before this road opens up.',
                'hint' => null,
            ],
            419 => [
                'sign' => 'Fire went out',
                'title' => 'This page sat too long',
                'body' => 'Your session went cold while the page was open. Nothing is broken, it just needs relighting.',
                'hint' => 'Load it again and send it through.',
            ],
            429 => [
                'sign' => 'Ease off',
                'title' => 'You are on the throttle a bit hard',
                'body' => 'That is more requests than this server wants to take at once. Give it a minute to cool off.',
                'hint' => null,
            ],
            500 => [
                'sign' => 'Broken down',
                'title' => 'Something let go under the hood',
                'body' => 'That one is on me, not on you. I have the hood up and a light on it.',
                'hint' => 'Try again shortly.',
            ],
            503 => [
                'sign' => 'Camp closed',
                'title' => 'Wrenching on things back here',
                'body' => 'The site is down on purpose while I work on it. It will not be long.',
                'hint' => null,
            ],
        ];
    }

    /**
     * @return array{sign: string, title: string, body: string, hint: string|null}
     */
    protected static function fallback(int $status): array
    {
        return $status >= 500
            ? [
                'sign' => 'Broken down',
                'title' => 'Something let go under the hood',
                'body' => 'That one is on me, not on you. I have the hood up and a light on it.',
                'hint' => 'Try again shortly.',
            ]
            : [
                'sign' => 'Wrong turn',
                'title' => 'This road does not go where you thought',
                'body' => 'The request did not make it through. No damage done.',
                'hint' => 'Back it up and pick another route.',
            ];
    }
}
