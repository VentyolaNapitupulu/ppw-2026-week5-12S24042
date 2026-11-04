<?php

use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('buku.index');
});

Route::resource('buku', BukuController::class);
Route::resource('kategori', KategoriController::class);

Route::resource('peminjaman', PeminjamanController::class)->only(['index', 'create', 'store']);
Route::patch('peminjaman/{peminjaman}/kembalikan', [PeminjamanController::class, 'kembalikan'])
    ->name('peminjaman.kembalikan');