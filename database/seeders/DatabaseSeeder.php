<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Satuan;
use App\Models\SatuanKemasan;
use App\Models\Stok;
use App\Models\Supplier;
use App\Models\User;
use App\Services\TransaksiService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun admin pertama: tanpa ini instalasi baru tidak bisa dipakai sama
        // sekali karena tidak ada jalan masuk ke aplikasi.
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@warungku.test',
                'password' => Hash::make(config('warungku.admin_password', 'admin12345')),
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::updateOrCreate(
            ['username' => 'kasir'],
            [
                'name' => 'Kasir Satu',
                'email' => 'kasir@warungku.test',
                'password' => Hash::make('kasir12345'),
                'role' => User::ROLE_KASIR,
            ]
        );

        // Master data wajib ada sebelum produk: form produk memakai dropdown
        // dari tabel ini, jadi kalau kosong form tidak bisa disimpan.
        $this->seedMasterData();

        // Data contoh hanya untuk development, supaya kasir dan laporan
        // langsung bisa dicoba tanpa mengetik produk satu per satu.
        if (app()->environment('local', 'testing')) {
            $this->seedProdukContoh();
        }
    }

    /**
     * Master data dasar: satuan & satuan kemasan.
     *
     * Kategori dan supplier sengaja TIDAK diisi di sini: keduanya butuh
     * keputusan pemilik (kode barang per kategori, daftar supplier aslinya).
     * Admin bisa menambahkannya lewat halaman master data.
     */
    private function seedMasterData(): void
    {
        // Nama ditulis dengan huruf awal besar, sama seperti yang dilakukan
        // MasterDataController — supaya data bawaan dan data yang dibuat
        // admin lewat form konsisten.
        foreach (['pcs', 'kg', 'gram', 'liter', 'ml', 'bungkus', 'botol', 'sachet'] as $nama) {
            Satuan::firstOrCreate(['nama' => huruf_awal_besar($nama)]);
        }

        foreach (['karung', 'dus', 'renceng', 'paket', 'sak'] as $nama) {
            SatuanKemasan::firstOrCreate(['nama' => huruf_awal_besar($nama)]);
        }
    }

    private function seedProdukContoh(): void
    {
        $produk = [
            ['barcode' => '8991002101013', 'nama_produk' => 'Kopi Sachet Kapal Api', 'kategori' => 'Minuman', 'harga_beli' => 1500, 'harga_jual' => 2500, 'stok' => 120],
            ['barcode' => '8992388201012', 'nama_produk' => 'Teh Botol Sosro 350ml', 'kategori' => 'Minuman', 'harga_beli' => 3500, 'harga_jual' => 5000, 'stok' => 60],
            ['barcode' => '8993175101011', 'nama_produk' => 'Indomie Goreng', 'kategori' => 'Makanan', 'harga_beli' => 2800, 'harga_jual' => 3500, 'stok' => 90],
            ['barcode' => '8991002101020', 'nama_produk' => 'Gula Pasir 1kg', 'kategori' => 'Sembako', 'harga_beli' => 14000, 'harga_jual' => 17000, 'stok' => 25],
            ['barcode' => '8991002101037', 'nama_produk' => 'Beras Premium 5kg', 'kategori' => 'Sembako', 'harga_beli' => 62000, 'harga_jual' => 72000, 'stok' => 12],
            ['barcode' => '8991002101044', 'nama_produk' => 'Chitato Sapi Panggang 68g', 'kategori' => 'Snack', 'harga_beli' => 9000, 'harga_jual' => 12000, 'stok' => 40],
            ['barcode' => '8991002101051', 'nama_produk' => 'Sabun Lifebuoy 75g', 'kategori' => 'Kebutuhan Rumah', 'harga_beli' => 3500, 'harga_jual' => 5000, 'stok' => 3],
            ['barcode' => '8991002101068', 'nama_produk' => 'Air Mineral Aqua 600ml', 'kategori' => 'Minuman', 'harga_beli' => 2500, 'harga_jual' => 4000, 'stok' => 0],
        ];

        $service = app(TransaksiService::class);
        $admin = User::where('username', 'admin')->first();

        // Kategori & supplier contoh dibuat per kategori produk, supaya form
        // produk tetap punya pilihan yang valid.
        $supplierContoh = Supplier::firstOrCreate(
            ['nama' => 'Toko Sembako Jaya'],
            ['kode' => Supplier::kodeBerikutnya(), 'telepon' => '0812-3456-7890']
        );

        foreach ($produk as $item) {
            $stokAwal = $item['stok'];

            $kategori = Kategori::firstOrCreate(
                ['nama' => $item['kategori']],
                ['kode_barang' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $item['kategori']), 0, 3))]
            );

            // Key `stok` HARUS dibuang dari data yang ditulis ke database.
            //
            // Alasannya: `array_merge($item, ...)` masih membawa key 'stok'
            // beserta nilainya, sehingga kolom stok terisi langsung (120) tanpa
            // baris mutasi. Setelah itu service menambah 120 lagi lewat
            // mutasi, hasilnya 240 — stok dobel.
            //
            // Jadi: kolom stok SELALU 0 saat produk dibuat, dan stok awal
            // ditambahkan lewat service supaya ada jejaknya di stok_mutasis.
            //
            // Kolom `stok` juga sengaja tidak ikut di-update saat produk sudah
            // ada: kalau ikut, setiap `db:seed` akan menimpa stok produk jadi 0
            // dan data yang sudah dipakai kasir ikut hilang. Seeder harus
            // idempoten: aman dijalankan berulang tanpa merusak data.
            $dataProduk = $item;
            unset($dataProduk['stok']);
            unset($dataProduk['kategori']);

            $dataProduk['kategori_id'] = $kategori->id;
            $dataProduk['supplier_id'] = $supplierContoh->id;

            $ada = Stok::where('barcode', $item['barcode'])->first();

            if ($ada) {
                // Produk sudah ada: perbarui data produk saja, jangan sentuh
                // kolom stok. Kalau stok ikut ditulis, setiap `db:seed` akan
                // mengembalikan stok ke 0 dan memusnahkan penjualan yang
                // sudah tercatat.
                $ada->update(array_merge($dataProduk, ['stok_minimal' => 5]));
                $produkBaru = $ada;
            } else {
                // Produk baru: dibuat dengan stok 0, lalu stok awal ditambahkan
                // lewat service supaya ada baris mutasinya.
                $produkBaru = Stok::create(
                    array_merge($dataProduk, ['stok' => 0, 'stok_minimal' => 5])
                );
            }

            // Stok awal hanya ditambahkan kalau produk ini belum punya
            // riwayat mutasi sama sekali (artinya benar-benar baru dibuat).
            if ($stokAwal > 0 && ! $produkBaru->mutasis()->exists()) {
                $service->tambahStok($produkBaru, $stokAwal, 'Stok awal data contoh', $admin);
            }
        }
    }
}
