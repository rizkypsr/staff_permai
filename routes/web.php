<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PengembalianController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RekapAbsensiController;
use App\Http\Controllers\StokController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::post('absensi', [AbsensiController::class, 'store'])->name('absensi.store');
    Route::get('/rekap-absensi', [RekapAbsensiController::class, 'index'])->name('rekap-absensi');
    Route::get('/pengiriman', [PengirimanController::class, 'index'])->name('pengiriman');
    Route::get('/pengiriman/create', [PengirimanController::class, 'create'])->name('pengiriman.create');
    Route::get('/pengiriman/{id}', [PengirimanController::class, 'show'])->name('pengiriman.show');
    Route::get('/pengiriman/faktur/{id}/produk-nota', [PengirimanController::class, 'getProdukNota'])->name('pengiriman.faktur.produk-nota');
    Route::post('/pengiriman', [PengirimanController::class, 'store'])->name('pengiriman.store');
    Route::get('/pengembalian/create', [PengembalianController::class, 'create'])->name('pengembalian.create');
    Route::get('/pengembalian/pengiriman/{id}/detail', [PengembalianController::class, 'getPengirimanDetail'])->name('pengembalian.pengiriman.detail');
    Route::post('/pengembalian', [PengembalianController::class, 'store'])->name('pengembalian.store');
    Route::get('/stok', [StokController::class, 'index'])->name('stok');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::inertia('/settings', 'Settings')->name('settings');
});
