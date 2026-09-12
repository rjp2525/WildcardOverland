<?php

namespace App\Providers;

use App\Image\Transformations\OgCard;
use App\Services\OgCardRenderer;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

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
         * Shared from here rather than from the Inertia middleware, because
         * the middleware does not run when an error response is rendered and
         * the footer is on those pages too. None of it depends on the
         * request, so a provider is where it belongs anyway.
         */
        Inertia::share('site', fn () => [
            'social' => array_filter(config('site.social')),
            'email' => config('site.email'),
            'since' => config('site.since'),
            'notesShortcut' => array_filter(config('site.notes_shortcut')),
        ]);

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
