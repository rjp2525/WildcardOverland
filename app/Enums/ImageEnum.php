<?php

namespace App\Enums;

enum ImageEnum {
    case SVG;
    case JPG;
    case PNG;
    case WEBP;

    public const ALLOWED_WIDTHS = [
        1600, 1200, 900, 600, 300, 146
    ];

    public const ALLOWED_HEIGHTS = [
        900, 630, 628, 600, 300, 146
    ];

    public const ALLOWED_TYPES = ['jpg', 'png'];

    public const ALLOWED_QUALITIES = [70, 80, 90];
    public const DEFAULT_QUALITY = 80;

    public const ALLOWED_FITMENTS = ['crop-center', 'max'];
    public const DEFAULT_FITMENT = 'max';
}
