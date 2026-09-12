<?php

namespace App\Providers;

use App\Image\Transformations\OgCard;
use App\Services\OgCardRenderer;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Link cards are drawn through the same image pipeline as every other
         * derivative, so they get the same driver handling and caching. The
         * text drawing itself is the one thing the framework's wrapper does
         * not cover, so it is registered as a custom transformation.
         */
        foreach (['gd', 'imagick'] as $driver) {
            Image::transformUsing(
                $driver,
                OgCard::class,
                fn ($image, OgCard $card) => app(OgCardRenderer::class)->apply($image, $card),
            );
        }

        //
    }
}
