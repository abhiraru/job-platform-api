<?php

use Illuminate\Support\Facades\Route;
use Modules\AI\Http\Controllers\AIController;

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    Route::get('/ai/match/{job}', [AIController::class, 'matchJob']);
});
