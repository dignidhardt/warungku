<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StokController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

// --- 1. AUTHENTICATION ---
Route::redirect('/', '/login');
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- 2. RUTE TERPROTEKSI (HARUS LOGIN) ---
Route::middleware('auth')->group(function () {

    // Dashboard & Home
    Route::get('/home', function () {

        $totalProduk = DB::table('stok')->count();

        $transaksiHariIni = DB::table('transaksis')
            ->whereDate('created_at', Carbon::today())
            ->count();

        $totalPendapatanHariIni = DB::table('transaksis')
            ->whereDate('created_at', Carbon::today())
            ->sum('grand_total');

        return view('home', compact(
            'totalProduk',
            'transaksiHariIni',
            'totalPendapatanHariIni'
        ));
    })->name('home');

    // ==========================
    // AKSES ADMIN & KASIR
    // ==========================

    Route::resource('transaksi', TransaksiController::class);

    Route::get('/stok', [StokController::class, 'index'])->name('stok.index');

    // Laporan bisa diakses Admin & Kasir
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export-excel', [LaporanController::class, 'exportExcel'])->name('laporan.excel');

    // ==========================
    // KHUSUS ADMIN
    // ==========================

    Route::middleware(\App\Http\Middleware\IsAdmin::class)->group(function () {

        // Manajemen Karyawan
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');

        // CRUD Stok
        Route::get('/stok/create', [StokController::class, 'create'])->name('stok.create');
        Route::post('/stok', [StokController::class, 'store'])->name('stok.store');
        Route::get('/stok/{stok}/edit', [StokController::class, 'edit'])->name('stok.edit');
        Route::put('/stok/{stok}', [StokController::class, 'update'])->name('stok.update');
        Route::delete('/stok/{stok}', [StokController::class, 'destroy'])->name('stok.destroy');
    });
});