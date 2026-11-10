<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\BukuApiController;
use App\Http\Controllers\Api\V1\PeminjamanApiController;
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

    // Katalog buku (baca)
    Route::get('/bukus', [BukuApiController::class, 'index']);
    Route::get('/bukus/{id}', [BukuApiController::class, 'show'])->whereNumber('id');

    // =====================================================================
    // 2. ENDPOINT TERPROTEKSI BEARER TOKEN (auth:sanctum)
    // =====================================================================
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthTokenController::class, 'me']);
        Route::post('/auth/logout', [AuthTokenController::class, 'logout']);

        // Mutasi katalog buku
        Route::post('/bukus', [BukuApiController::class, 'store']);
        Route::put('/bukus/{id}', [BukuApiController::class, 'update'])->whereNumber('id');
        Route::patch('/bukus/{id}', [BukuApiController::class, 'update'])->whereNumber('id');
        Route::delete('/bukus/{id}', [BukuApiController::class, 'destroy'])->whereNumber('id');

        // Transaksi sirkulasi (ACID)
        Route::get('/peminjamans', [PeminjamanApiController::class, 'index']);
        Route::get('/peminjamans/{id}', [PeminjamanApiController::class, 'show'])->whereNumber('id');
        Route::post('/peminjamans', [PeminjamanApiController::class, 'store']);
        Route::patch('/peminjamans/{id}/kembalikan', [PeminjamanApiController::class, 'kembalikan'])->whereNumber('id');
    });
});