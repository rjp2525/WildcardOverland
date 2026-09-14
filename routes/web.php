<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\OgCardController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RecipeFeedbackController;
use App\Http\Controllers\RigController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomepageController::class)
    ->name('homepage');
Route::get('about', AboutController::class)
    ->name('about');
// "/build" would collide with Vite's output directory in public/.
Route::get('rig', RigController::class)
    ->name('rig');

Route::get('trips', [TripController::class, 'index'])->name('trips.index');
Route::get('trips/{trip:slug}', [TripController::class, 'show'])->name('trips.show');

Route::get('recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('recipes/{recipe:slug}', [RecipeController::class, 'show'])->name('recipes.show');

/*
 * Feedback from people who cooked it. Open to anyone, so throttled by
 * address: the honeypot turns away the scripts that do not look at the page,
 * and this turns away the ones that do.
 */
Route::post('recipes/{recipe:slug}/rating', [RecipeFeedbackController::class, 'rate'])
    ->middleware('throttle:feedback-ratings')
    ->name('recipes.rate');

Route::post('recipes/{recipe:slug}/comments', [RecipeFeedbackController::class, 'comment'])
    ->middleware('throttle:feedback-comments')
    ->name('recipes.comment');

/*
 * Served rather than kept in public/ so the sitemap line always points at
 * the host the site is actually running on.
 */
Route::get('robots.txt', fn () => response(
    implode("\n", [
        'User-agent: *',
        'Allow: /',
        '',
        '# Nothing here is useful to a crawler.',
        'Disallow: /admin',
        '',
        'Sitemap: '.route('sitemap'),
        '',
    ]),
    200,
    ['Content-Type' => 'text/plain'],
))->name('robots');

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

// Unsigned on purpose: a crawler has to be able to fetch this cold, and the
// platforms cache it hard, so the URL has to stay put.
Route::get('og/{kind}/{slug}.jpg', OgCardController::class)
    ->whereIn('kind', ['trips', 'recipes', 'page'])
    ->name('og.card');

Route::get('assets/{path}', AssetController::class)
    ->where('path', '.+')
    ->name('asset');
