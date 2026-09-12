<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | The driver Laravel's image manipulation uses. GD ships with almost every
    | PHP build; Imagick is faster and handles more formats, so use it where
    | it is available.
    |
    | Supported: "gd", "imagick"
    |
    */

    'default' => env('IMAGE_DRIVER', 'gd'),

];
