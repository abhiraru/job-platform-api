<?php

use Illuminate\Support\Facades\Route;
use Modules\Jobs\Http\Controllers\JobController;

Route::prefix('api')->middleware('api')->group(function (): void {
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{job}', [JobController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/jobs', [JobController::class, 'store']);
        Route::delete('/jobs/{job}', [JobController::class, 'destroy']);
    });
});
