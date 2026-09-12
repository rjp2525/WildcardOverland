<?php

namespace Database\Seeders\Demo;

/**
 * Produces placeholder landscape images for demo content.
 *
 * Deterministic: the same key always yields byte-identical output. That makes
 * seeding idempotent (FileUploadService deduplicates on the SHA-256) and lets
 * the cleanup seeder find exactly these files again by hash.
 *
 * These are obviously not photographs - they exist so the grid, gallery and
 * cards are not empty while real images are still being collected.
 */
class DemoImageFactory
{
    public const WIDTH = 1600;

    public const HEIGHT = 900;

    /**
     * Sky palettes, roughly dawn → dusk, so seeded cards differ from each other.
     *
     * @var array<int, array{0: array<int,int>, 1: array<int,int>, 2: array<int,int>}>
     */
    protected const PALETTES = [
        [[247, 183, 106], [232, 90, 47], [61, 42, 51]],     // desert sunset
        [[136, 186, 214], [227, 231, 226], [58, 74, 79]],   // overcast coast
        [[38, 62, 99], [122, 96, 140], [24, 26, 38]],       // dusk
        [[250, 214, 137], [138, 178, 148], [45, 59, 48]],   // high meadow
        [[196, 224, 235], [120, 160, 190], [41, 52, 63]],   // alpine
        [[255, 196, 140], [206, 108, 74], [70, 47, 46]],    // canyon
        [[27, 42, 65], [70, 92, 122], [17, 20, 28]],        // night
        [[233, 220, 194], [176, 148, 112], [66, 56, 45]],   // dunes
    ];

    public function png(string $key): string
    {
        // A stable integer seed derived from the key.
        $seed = (int) hexdec(substr(md5($key), 0, 7));
        mt_srand($seed);

        $palette = self::PALETTES[$seed % count(self::PALETTES)];
        [$skyTop, $skyBottom, $land] = $palette;

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $horizon = (int) (self::HEIGHT * (0.55 + (($seed % 17) / 100)));

        $this->paintSky($image, $skyTop, $skyBottom, $horizon);
        $this->paintSun($image, $seed, $horizon, $skyTop);
        $this->paintRidges($image, $seed, $horizon, $land);
        $this->paintForeground($image, $horizon, $land);

        ob_start();
        imagepng($image, null, 9);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        mt_srand();

        return $png;
    }

    /**
     * @param  array<int,int>  $top
     * @param  array<int,int>  $bottom
     */
    protected function paintSky(\GdImage $image, array $top, array $bottom, int $horizon): void
    {
        for ($y = 0; $y < $horizon; $y++) {
            $t = $y / max(1, $horizon);
            $colour = imagecolorallocate(
                $image,
                (int) ($top[0] + ($bottom[0] - $top[0]) * $t),
                (int) ($top[1] + ($bottom[1] - $top[1]) * $t),
                (int) ($top[2] + ($bottom[2] - $top[2]) * $t),
            );
            imageline($image, 0, $y, self::WIDTH, $y, $colour);
        }
    }

    /**
     * @param  array<int,int>  $tint
     */
    protected function paintSun(\GdImage $image, int $seed, int $horizon, array $tint): void
    {
        $cx = (int) (self::WIDTH * (0.2 + (($seed % 11) / 18)));
        $cy = (int) ($horizon * 0.62);
        $r = 90 + ($seed % 50);

        $sun = imagecolorallocatealpha(
            $image,
            min(255, $tint[0] + 25),
            min(255, $tint[1] + 25),
            min(255, $tint[2] + 25),
            40,
        );
        imagefilledellipse($image, $cx, $cy, $r * 2, $r * 2, $sun);
    }

    /**
     * Two overlapping ridge lines, the far one lightened, for a sense of depth.
     *
     * @param  array<int,int>  $land
     */
    protected function paintRidges(\GdImage $image, int $seed, int $horizon, array $land): void
    {
        foreach ([0.55, 1.0] as $depth) {
            $shade = imagecolorallocate(
                $image,
                (int) ($land[0] + (255 - $land[0]) * (1 - $depth) * 0.55),
                (int) ($land[1] + (255 - $land[1]) * (1 - $depth) * 0.55),
                (int) ($land[2] + (255 - $land[2]) * (1 - $depth) * 0.55),
            );

            $points = [];
            $baseline = $horizon + (int) ((1 - $depth) * -60);
            $amplitude = 70 + ($seed % 90) * $depth;
            $step = 80;

            for ($x = 0; $x <= self::WIDTH + $step; $x += $step) {
                $y = $baseline
                    - (int) (sin(($x / self::WIDTH) * M_PI * (1.5 + $depth)) * $amplitude)
                    - mt_rand(0, (int) (30 * $depth));
                $points[] = $x;
                $points[] = $y;
            }

            // Close the polygon along the bottom edge.
            $points[] = self::WIDTH + $step;
            $points[] = self::HEIGHT;
            $points[] = -$step;
            $points[] = self::HEIGHT;

            imagefilledpolygon($image, $points, $shade);
        }
    }

    /**
     * @param  array<int,int>  $land
     */
    protected function paintForeground(\GdImage $image, int $horizon, array $land): void
    {
        $dark = imagecolorallocate(
            $image,
            (int) ($land[0] * 0.5),
            (int) ($land[1] * 0.5),
            (int) ($land[2] * 0.5),
        );
        imagefilledrectangle($image, 0, (int) (self::HEIGHT * 0.93), self::WIDTH, self::HEIGHT, $dark);
    }
}
