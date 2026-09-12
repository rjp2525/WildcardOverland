<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\RecipeController;
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

Route::get('assets/{path}', AssetController::class)
    ->where('path', '.+')
    ->name('asset');
