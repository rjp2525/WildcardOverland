<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\NavigationLinkController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\VehicleModificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [SessionController::class, 'create'])->name('login');
        Route::post('login', [SessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('logout', [SessionController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('trips', TripController::class)->except('show');

        Route::resource('recipes', RecipeController::class)->except('show');

        Route::resource('navigation-links', NavigationLinkController::class)
            ->except('show')
            ->parameters(['navigation-links' => 'navigation_link']);

        Route::resource('vehicle-modifications', VehicleModificationController::class)
            ->except('show');

        Route::resource('brands', BrandController::class)->except('show');

        // Images are created by uploading a file, so there is no create/store.
        Route::resource('images', ImageController::class)
            ->only(['index', 'edit', 'update', 'destroy']);

        Route::resource('files', FileController::class)
            ->only(['index', 'store', 'destroy']);
    });
});
