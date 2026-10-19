<?php

namespace Database\Seeders;

use App\Models\Stok;
use App\Models\User;
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

        // Data contoh hanya untuk development, supaya kasir dan laporan
        // langsung bisa dicoba tanpa mengetik produk satu per satu.
        if (app()->environment('local', 'testing')) {
            $this->seedProdukContoh();
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

        foreach ($produk as $item) {
            Stok::updateOrCreate(
                ['barcode' => $item['barcode']],
                $item + ['stok_minimal' => 5]
            );
        }
    }
}
