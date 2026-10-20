<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsAdminOrGudang;
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
|
| PEMISAHAN HAK AKSES:
| - Kasir: kasir, riwayat, stok (lihat), laporan penjualan, tutup buku.
| - Admin: pembelian, retur, pengeluaran, laba rugi, buku kas, kelola
|   karyawan & produk, pembatalan transaksi.
| Alasan tutup buku boleh kasir: itu pekerjaan akhir shift, bukan wewenang
| pemilik. Tapi melihat laba rugi dan buku kas tetap admin, karena di situ
| terlihat seluruh kondisi keuangan usaha.
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

    // Riwayat: semua peran boleh melihat, hanya admin yang boleh mengoreksi.
    Route::get('/riwayat', [TransaksiController::class, 'riwayat'])->name('transaksi.riwayat');

    // ---------- Laporan penjualan (admin & kasir) ----------
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export-excel', [LaporanController::class, 'exportExcel'])->name('laporan.excel');
    Route::get('/laporan/export-pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');

    // ---------- Tutup buku: pekerjaan akhir shift, boleh kasir ----------
    Route::get('/tutup-buku', [KeuanganController::class, 'tutupBuku'])->name('keuangan.tutup-buku');
    Route::post('/tutup-buku', [KeuanganController::class, 'simpanTutupBuku'])->name('keuangan.tutup-buku.simpan');

    // ---------- Stok: daftar boleh semua peran ----------
    Route::get('/stok', [StokController::class, 'index'])->name('stok.index');

    // ---------- Stok & Pembelian: admin + gudang ----------
    //
    // Hak akses "Gudang": read/write Produk & Stok, Pembelian, dan
    // Supplier — sama seperti admin untuk menu-menu itu. Yang tetap khusus
    // admin saja: master data, keuangan, retur, dan karyawan.
    Route::middleware(IsAdminOrGudang::class)->group(function () {

        // ---------- Pembelian ----------
        Route::get('/pembelian', [PembelianController::class, 'index'])->name('pembelian.index');
        Route::get('/pembelian/create', [PembelianController::class, 'create'])->name('pembelian.create');
        Route::post('/pembelian', [PembelianController::class, 'store'])->name('pembelian.store');
        Route::get('/pembelian/{pembelian}', [PembelianController::class, 'show'])->name('pembelian.show');
        Route::delete('/pembelian/{pembelian}', [PembelianController::class, 'destroy'])
            ->name('pembelian.batal');

        // ---------- Supplier ----------
        Route::get('/supplier', [SupplierController::class, 'index'])->name('master.supplier');
        Route::get('/supplier/create', [SupplierController::class, 'create'])->name('master.supplier.create');
        Route::post('/supplier', [SupplierController::class, 'store'])->name('master.supplier.store');
        Route::get('/supplier/{supplier}/edit', [SupplierController::class, 'edit'])->name('master.supplier.edit');
        Route::put('/supplier/{supplier}', [SupplierController::class, 'update'])->name('master.supplier.update');
        Route::delete('/supplier/{supplier}', [SupplierController::class, 'destroy'])->name('master.supplier.hapus');

        // Produk: rute statis dulu, baru yang berparameter.
        Route::get('/stok/create', [StokController::class, 'create'])->name('stok.create');
        Route::post('/stok', [StokController::class, 'store'])->name('stok.store');
        Route::get('/stok/{stok}/edit', [StokController::class, 'edit'])->name('stok.edit');
        Route::put('/stok/{stok}', [StokController::class, 'update'])->name('stok.update');
        Route::delete('/stok/{stok}', [StokController::class, 'destroy'])->name('stok.destroy');
    });

    // ---------- Khusus admin ----------
    Route::middleware(IsAdmin::class)->group(function () {

        // Koreksi transaksi yang sudah terjadi.
        Route::post('/transaksi/{transaksi}/batal', [TransaksiController::class, 'batal'])
            ->name('transaksi.batal');

        Route::get('/transaksi/{transaksi}/retur', [TransaksiController::class, 'formRetur'])
            ->name('transaksi.retur');
        Route::post('/transaksi/{transaksi}/retur', [TransaksiController::class, 'simpanRetur'])
            ->name('transaksi.retur.simpan');

        // ---------- Keuangan ----------
        Route::get('/keuangan', [KeuanganController::class, 'index'])->name('keuangan.index');
        Route::get('/keuangan/laba-rugi', [KeuanganController::class, 'labaRugi'])->name('keuangan.laba-rugi');
        Route::get('/keuangan/buku-kas', [KeuanganController::class, 'bukuKas'])->name('keuangan.buku-kas');
        Route::get('/keuangan/pengeluaran', [KeuanganController::class, 'pengeluaran'])->name('keuangan.pengeluaran');
        Route::post('/keuangan/pengeluaran', [KeuanganController::class, 'simpanPengeluaran'])
            ->name('keuangan.pengeluaran.simpan');
        Route::delete('/keuangan/pengeluaran/{pengeluaran}', [KeuanganController::class, 'hapusPengeluaran'])
            ->name('keuangan.pengeluaran.hapus');

        // Manajemen karyawan
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // ---------- Master data: kategori, satuan, satuan kemasan ----------
        Route::get('/master/kategori', [MasterDataController::class, 'kategori'])->name('master.kategori');
        Route::post('/master/kategori', [MasterDataController::class, 'simpanKategori'])->name('master.kategori.simpan');
        Route::put('/master/kategori/{kategori}', [MasterDataController::class, 'ubahKategori'])->name('master.kategori.ubah');
        Route::delete('/master/kategori/{kategori}', [MasterDataController::class, 'hapusKategori'])->name('master.kategori.hapus');

        Route::get('/master/satuan', [MasterDataController::class, 'satuan'])->name('master.satuan');
        Route::post('/master/satuan', [MasterDataController::class, 'simpanSatuan'])->name('master.satuan.simpan');
        Route::put('/master/satuan/{satuan}', [MasterDataController::class, 'ubahSatuan'])->name('master.satuan.ubah');
        Route::delete('/master/satuan/{satuan}', [MasterDataController::class, 'hapusSatuan'])->name('master.satuan.hapus');

        Route::get('/master/satuan-kemasan', [MasterDataController::class, 'satuanKemasan'])->name('master.satuan-kemasan');
        Route::post('/master/satuan-kemasan', [MasterDataController::class, 'simpanSatuanKemasan'])->name('master.satuan-kemasan.simpan');
        Route::put('/master/satuan-kemasan/{satuanKemasan}', [MasterDataController::class, 'ubahSatuanKemasan'])->name('master.satuan-kemasan.ubah');
        Route::delete('/master/satuan-kemasan/{satuanKemasan}', [MasterDataController::class, 'hapusSatuanKemasan'])->name('master.satuan-kemasan.hapus');

        // Penyesuaian & penambahan stok (stok opname, barang datang)
        Route::post('/stok/{stok}/sesuaikan', [StokController::class, 'sesuaikan'])->name('stok.sesuaikan');
        Route::post('/stok/{stok}/tambah', [StokController::class, 'tambah'])->name('stok.tambah');
    });

    // Detail produk didaftarkan TERAKHIR agar tidak menelan /stok/create
    // maupun /stok/{id}/edit.
    Route::get('/stok/{stok}', [StokController::class, 'show'])->name('stok.show');
});
