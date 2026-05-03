<?php

use Illuminate\Support\Facades\Route;
use Modules\Applications\Http\Controllers\ApplicationController;

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    Route::post('/apply', [ApplicationController::class, 'apply']);
    Route::get('/my-applications', [ApplicationController::class, 'myApplications']);
    Route::get('/jobs/{job}/applications', [ApplicationController::class, 'jobApplications']);
    Route::post('/applications/{application}/status', [ApplicationController::class, 'updateStatus']);
});
