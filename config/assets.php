<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Asset Storage
    |--------------------------------------------------------------------------
    |
    | Originals are written to the `disk`, and derivatives are cached on the
    | `cache_disk`. Both are local by default: originals are served through
    | the application rather than the web server, so there is nothing to gain
    | from putting them in a bucket.
    |
    */

    'disk' => env('ASSET_DISK', 'assets'),

    'cache_disk' => env('ASSET_CACHE_DISK', 'asset-cache'),

    'upload_path' => env('ASSET_UPLOAD_PATH', 'uploads'),

    /*
    | Maximum accepted upload size, in kilobytes.
    */
    'max_upload_kb' => (int) env('ASSET_MAX_UPLOAD_KB', 20480),

    /*
    | WebP quality for generated derivatives. 78 is around the point where
    | artefacts stop being visible on photographs.
    */
    'quality' => (int) env('ASSET_QUALITY', 78),

    /*
    | How long a browser may keep a derivative. They are immutable - a new
    | source is written to a new path - so this can be long.
    */
    'max_age' => (int) env('ASSET_MAX_AGE', 31536000),

    /*
    |--------------------------------------------------------------------------
    | Variants
    |--------------------------------------------------------------------------
    |
    | Every image URL names a variant and a width, and both are signed. That
    | bounds what can be generated to this table: there is no way to ask the
    | server for an arbitrary size, so the derivative cache cannot be made to
    | grow without limit.
    |
    | fit     "cover" fills the box and crops the overflow; "scale" fits the
    |         whole image inside the box without padding it.
    | ratio   [w, h] the box is cropped to, or null to keep the original's.
    | widths  The srcset ladder. The last one is what `src` points at.
    | sizes   The default `sizes` attribute - how wide the image actually
    |         renders, so the browser can pick a candidate before layout.
    | format  Defaults to webp. Only worth overriding to keep transparency.
    |
    */

    'variants' => [

        'hero' => [
            'fit' => 'cover',
            'ratio' => [16, 9],
            'widths' => [640, 960, 1280, 1600, 1920],
            'sizes' => '100vw',
        ],

        'card' => [
            'fit' => 'cover',
            'ratio' => [3, 2],
            'widths' => [320, 480, 640, 960],
            'sizes' => '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw',
        ],

        'thumb' => [
            'fit' => 'cover',
            'ratio' => [1, 1],
            'widths' => [240, 320, 480, 640],
            'sizes' => '(min-width: 640px) 50vw, 100vw',
        ],

        'logo' => [
            'fit' => 'scale',
            'ratio' => null,
            'widths' => [160, 320],
            'sizes' => '160px',
            // Logos are flat colour with transparency, which webp handles,
            // but png stays smaller at this size.
            'format' => 'png',
        ],

    ],

];
