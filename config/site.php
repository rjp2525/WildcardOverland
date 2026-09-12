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
    |--------------------------------------------------------------------------
    | The shopping list shortcut
    |--------------------------------------------------------------------------
    |
    | Apple Notes will not build a checklist from text no matter what shape it
    | arrives in. The only thing that can is the Shortcuts app, through its own
    | append-checklist action, and Apple documents a URL scheme for handing a
    | shortcut some input.
    |
    | Set `name` to the shortcut's exact name and `install` to its iCloud share
    | link. With both present the recipe pages offer to send the list straight
    | to Notes as a real checklist; with neither they fall back to the share
    | sheet, which every device has.
    |
    */

    'notes_shortcut' => [
        'name' => env('NOTES_SHORTCUT_NAME'),
        'install' => env('NOTES_SHORTCUT_URL'),
    ],

    /*
    | The year the site started, for the copyright line.
    */
    'since' => (int) env('SITE_SINCE', 2024),

];
