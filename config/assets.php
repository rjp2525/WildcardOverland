<?php

return [
    'signature' => env('ASSET_SIGNATURE'),

    /*
    |--------------------------------------------------------------------------
    | Asset Storage
    |--------------------------------------------------------------------------
    |
    | The disk uploaded files are written to, and that the Glide image server
    | reads from. These must agree, otherwise uploads will not be servable.
    | Set ASSET_DISK=public for local development without S3 credentials.
    |
    */

    'disk' => env('ASSET_DISK', 's3'),

    'upload_path' => env('ASSET_UPLOAD_PATH', 'uploads'),

    /*
    | Maximum accepted upload size, in kilobytes.
    */
    'max_upload_kb' => (int) env('ASSET_MAX_UPLOAD_KB', 20480),
];
