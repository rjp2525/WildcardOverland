<?php

namespace App\Providers;

use App\Image\Transformations\OgCard;
use App\Services\OgCardRenderer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->rateLimitFeedback();

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

    /**
     * How often one address may send something in.
     *
     * Keyed on the address rather than the visitor cookie on purpose. A
     * script that wanted around this would simply not keep the cookie, and
     * the point of a limit is that it binds the people trying to get past it.
     */
    protected function rateLimitFeedback(): void
    {
        RateLimiter::for('feedback-reviews', fn (Request $request) => Limit::perHour(
            (int) config('feedback.throttle.reviews_per_hour'),
        )->by($request->ip() ?? 'unknown'));
    }
}
