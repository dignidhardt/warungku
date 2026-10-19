<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Warungku
|--------------------------------------------------------------------------
| Struktur sengaja eksplisit (bukan Route::resource) karena controller hanya
| mengimplementasikan aksi yang benar-benar dipakai. Route::resource mendaftar
| 7 aksi; kalau controller tidak punya method-nya, halaman 500.
|
| URUTAN PENTING: rute statis harus didaftarkan SEBELUM rute berparameter
| dengan prefiks sama. `/stok/create` wajib di atas `/stok/{stok}`, kalau tidak
| "create" akan dianggap id produk dan halaman jadi 404.
*/

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {

    // ---------- Dashboard ----------
    Route::get('/home', DashboardController::class)->name('home');
    Route::get('/dashboard', DashboardController::class);

    // ---------- Kasir / transaksi (admin & kasir) ----------
    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
    Route::get('/transaksi/{transaksi}/struk', [TransaksiController::class, 'struk'])->name('transaksi.struk');

    // Riwayat: semua peran boleh melihat, hanya admin yang boleh membatalkan.
    Route::get('/riwayat', [TransaksiController::class, 'riwayat'])->name('transaksi.riwayat');

    // ---------- Laporan (admin & kasir) ----------
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export-excel', [LaporanController::class, 'exportExcel'])->name('laporan.excel');
    Route::get('/laporan/export-pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');

    // ---------- Stok: daftar boleh semua peran ----------
    Route::get('/stok', [StokController::class, 'index'])->name('stok.index');

    // ---------- Khusus admin ----------
    Route::middleware(IsAdmin::class)->group(function () {

        // Transaksi tercatat tidak boleh hilang tanpa jejak.
        Route::post('/transaksi/{transaksi}/batal', [TransaksiController::class, 'batal'])
            ->name('transaksi.batal');

        // Manajemen karyawan
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Produk: rute statis dulu, baru yang berparameter.
        Route::get('/stok/create', [StokController::class, 'create'])->name('stok.create');
        Route::post('/stok', [StokController::class, 'store'])->name('stok.store');
        Route::get('/stok/{stok}/edit', [StokController::class, 'edit'])->name('stok.edit');
        Route::put('/stok/{stok}', [StokController::class, 'update'])->name('stok.update');
        Route::delete('/stok/{stok}', [StokController::class, 'destroy'])->name('stok.destroy');

        // Penyesuaian & penambahan stok (stok opname, barang datang)
        Route::post('/stok/{stok}/sesuaikan', [StokController::class, 'sesuaikan'])->name('stok.sesuaikan');
        Route::post('/stok/{stok}/tambah', [StokController::class, 'tambah'])->name('stok.tambah');
    });

    // Detail produk didaftarkan TERAKHIR agar tidak menelan /stok/create
    // maupun /stok/{id}/edit.
    Route::get('/stok/{stok}', [StokController::class, 'show'])->name('stok.show');
});
