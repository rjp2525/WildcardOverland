<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Where to find Reno
    |--------------------------------------------------------------------------
    |
    | Only the ones with a value are rendered, so leaving a handle blank
    | removes it from the footer rather than linking somewhere broken.
    |
    */

    'social' => [
        'instagram' => env('SOCIAL_INSTAGRAM'),
        'youtube' => env('SOCIAL_YOUTUBE'),
        'tiktok' => env('SOCIAL_TIKTOK'),
    ],

    'email' => env('SITE_EMAIL'),

    /*
    | The year the site started, for the copyright line.
    */
    'since' => (int) env('SITE_SINCE', 2024),

];
