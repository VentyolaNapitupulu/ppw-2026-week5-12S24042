<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('buku.index');
});

// Tamu: halaman login
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
});

// Wajib login: logout, semua rute mutasi, dan sirkulasi peminjaman
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::resource('buku', BukuController::class)->except(['index', 'show']);
    Route::resource('kategori', KategoriController::class)->except(['index', 'show']);

    Route::resource('peminjaman', PeminjamanController::class)->only(['index', 'create', 'store']);
    Route::patch('peminjaman/{peminjaman}/kembalikan', [PeminjamanController::class, 'kembalikan'])
        ->name('peminjaman.kembalikan');
});

// Publik: hanya membaca
Route::resource('buku', BukuController::class)->only(['index', 'show']);
Route::resource('kategori', KategoriController::class)->only(['index', 'show']);