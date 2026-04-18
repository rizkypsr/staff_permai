<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PengirimanController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::post('absensi', [AbsensiController::class, 'store'])->name('absensi.store');
    Route::get('/pengiriman', [PengirimanController::class, 'index'])->name('pengiriman');
    Route::get('/pengiriman/create', [PengirimanController::class, 'create'])->name('pengiriman.create');
    Route::get('/pengiriman/{id}', [PengirimanController::class, 'show'])->name('pengiriman.show');
    Route::get('/pengiriman/faktur/{id}/produk-nota', [PengirimanController::class, 'getProdukNota'])->name('pengiriman.faktur.produk-nota');
    Route::post('/pengiriman', [PengirimanController::class, 'store'])->name('pengiriman.store');
    Route::inertia('/stok', 'Stok')->name('stok');
    Route::inertia('/settings', 'Settings')->name('settings');
});
