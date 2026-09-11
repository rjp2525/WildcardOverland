<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\HomepageController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomepageController::class)
    ->name('homepage');
Route::get('about', AboutController::class)
    ->name('about');

Route::get('assets/{path}', AssetController::class)
    ->where('path', '.+')
    ->name('asset');
