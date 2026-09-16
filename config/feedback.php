<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ratings
    |--------------------------------------------------------------------------
    |
    | Google will show a star rating in a search result from `aggregateRating`,
    | but it has to be a real one. A single star from a single browser is not
    | a rating anybody should be shown, and a search result carrying a number
    | that nobody stands behind is how a site loses the rich result and the
    | trust with it. So the page shows every rating it has, and the markup
    | only claims one once there are enough of them to mean something.
    |
    */

    'ratings' => [
        'min_for_schema' => (int) env('FEEDBACK_MIN_RATINGS_FOR_SCHEMA', 3),

    ],

    /*
    |--------------------------------------------------------------------------
    | Comments and photographs
    |--------------------------------------------------------------------------
    |
    | Nobody signs in, so nothing from a stranger reaches the page before it
    | has been read. Turning this off would make the site a spam host inside
    | a week, so it is a setting rather than a constant only because a closed
    | test site might want it.
    |
    */

    'comments' => [
        /*
         * Whether the address given has to be answered before the review
         * behind it goes anywhere. Asking for an address raises the cost of
         * inventing a review; answering one is what makes it real, and it
         * is the only check here that a script cannot simply outwait.
         *
         * Turning this off is for a site with no mailer configured. It is
         * not a setting to reach for because the emails are inconvenient.
         */
        'confirm' => (bool) env('FEEDBACK_CONFIRM_EMAIL', true),

        /** How long the link in that email keeps working. */
        'confirm_days' => (int) env('FEEDBACK_CONFIRM_DAYS', 7),

        'moderate' => (bool) env('FEEDBACK_MODERATE_COMMENTS', true),
        'max_length' => 2000,
        'photos' => (bool) env('FEEDBACK_ALLOW_PHOTOS', true),
        'photo_max_kb' => (int) env('FEEDBACK_PHOTO_MAX_KB', 8192),
    ],

    /*
    |--------------------------------------------------------------------------
    | How often one person may post
    |--------------------------------------------------------------------------
    |
    | Per address per hour. The honeypot stops the scripts that do not look;
    | this stops the ones that do.
    |
    */

    'throttle' => [
        'reviews_per_hour' => (int) env('FEEDBACK_REVIEWS_PER_HOUR', 5),
    ],

];
