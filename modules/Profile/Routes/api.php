<?php

use Illuminate\Support\Facades\Route;
use Modules\Profile\Http\Controllers\ProfileController;

Route::prefix('api')->middleware('api')->group(function (): void {
    Route::prefix('profile')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [ProfileController::class, 'showCurrent']);
        Route::match(['put', 'patch'], '/me', [ProfileController::class, 'upsert']);
    });

    Route::get('/profiles/{user}', [ProfileController::class, 'showPublic']);
});
