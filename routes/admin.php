<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CommentController;
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

        /*
         * Trips and recipes go to the trash rather than straight out. The
         * restore and force routes have to resolve a record that is already
         * soft deleted, hence `withTrashed` on the binding.
         */
        Route::put('trips/{trip}/restore', [TripController::class, 'restore'])
            ->withTrashed()
            ->name('trips.restore');

        Route::delete('trips/{trip}/force', [TripController::class, 'forceDestroy'])
            ->withTrashed()
            ->name('trips.force-destroy');

        Route::resource('trips', TripController::class)->except('show');

        Route::put('recipes/{recipe}/restore', [RecipeController::class, 'restore'])
            ->withTrashed()
            ->name('recipes.restore');

        Route::delete('recipes/{recipe}/force', [RecipeController::class, 'forceDestroy'])
            ->withTrashed()
            ->name('recipes.force-destroy');

        Route::resource('recipes', RecipeController::class)->except('show');

        Route::resource('navigation-links', NavigationLinkController::class)
            ->except('show')
            ->parameters(['navigation-links' => 'navigation_link']);

        Route::resource('vehicle-modifications', VehicleModificationController::class)
            ->except('show');

        Route::resource('brands', BrandController::class)->except('show');

        // Images are created by uploading a file, so there is no create/store.
        // The dropzones post here and get the new image back as JSON.
        Route::post('images/upload', [ImageController::class, 'upload'])
            ->name('images.upload');

        Route::resource('images', ImageController::class)
            ->only(['index', 'edit', 'update', 'destroy']);

        // What people who cooked something sent in, waiting to be read.
        Route::get('comments', [CommentController::class, 'index'])->name('comments.index');
        Route::put('comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
        Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

        Route::resource('files', FileController::class)
            ->only(['index', 'store', 'destroy']);
    });
});
