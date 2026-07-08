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
        return view('home', compact('totalProduk', 'transaksiHariIni'));
    })->name('home');
    
    // Transaksi & Stok (Akses Umum Kasir & Admin)
    Route::resource('transaksi', TransaksiController::class);
    Route::get('/stok', [StokController::class, 'index'])->name('stok.index');

    // --- 3. KHUSUS ADMIN ---
    Route::middleware(\App\Http\Middleware\IsAdmin::class)->group(function () {
        
        // Manajemen Karyawan
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        // Tambahan: Fitur Edit User
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        
        // Laporan
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/export-excel', [LaporanController::class, 'exportExcel'])->name('laporan.excel');
        
        // CRUD Stok (Hanya Admin)
        Route::get('/stok/create', [StokController::class, 'create'])->name('stok.create');
        Route::post('/stok', [StokController::class, 'store'])->name('stok.store');
        Route::get('/stok/{stok}/edit', [StokController::class, 'edit'])->name('stok.edit');
        Route::put('/stok/{stok}', [StokController::class, 'update'])->name('stok.update');
        Route::delete('/stok/{stok}', [StokController::class, 'destroy'])->name('stok.destroy');
    });
});