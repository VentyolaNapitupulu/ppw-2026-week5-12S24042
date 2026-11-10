<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SIPUS-Del REST API v1  (prefix otomatis: /api)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // =====================================================================
    // 1. ENDPOINT PUBLIK (tanpa token)
    // =====================================================================
    // Login dibatasi 5 percobaan/menit per IP (cegah brute force -> 429)
    Route::post('/auth/login', [AuthTokenController::class, 'login'])
        ->middleware('throttle:5,1');

    // =====================================================================
    // 2. ENDPOINT TERPROTEKSI BEARER TOKEN (auth:sanctum)
    // =====================================================================
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthTokenController::class, 'me']);
        Route::post('/auth/logout', [AuthTokenController::class, 'logout']);
    });
});