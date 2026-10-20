<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master data: kategori, satuan, satuan kemasan, dan supplier.
 *
 * Kenapa dipisah dari tabel `stok`:
 * - Opsi kategori/satuan/satuan kemasan sebelumnya ditulis bebas sebagai
 *   teks di form produk. Akibatnya satu produk bisa punya kategori
 *   "Makanan" dan produk lain "makanan " (ada spasi), sehingga laporan
 *   per-kategori terpecah dan filter tidak akurat.
 * - Supplier juga merupakan entitas tersendiri (nama, kode, telepon,
 *   alamat) yang dipakai banyak produk, bukan atribut bebas per produk.
 *
 * Setelah tabel ini ada, form produk memakai dropdown yang diambil dari
 * sini, sehingga tidak ada lagi input string custom.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- Kategori ----------
        if (! Schema::hasTable('kategoris')) {
            Schema::create('kategoris', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 100)->unique();
                // "Kode Barang": 3 karakter (angka + huruf), mis. "AAA".
                // Dipakai sebagai prefiks Kode Barang produk, mis. "AAA-001".
                $table->string('kode_barang', 3)->unique();
                $table->timestamps();
            });
        }

        // ---------- Satuan ----------
        if (! Schema::hasTable('satuans')) {
            Schema::create('satuans', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 50)->unique();
                $table->timestamps();
            });
        }

        // ---------- Satuan Kemasan ----------
        if (! Schema::hasTable('satuan_kemisans')) {
            Schema::create('satuan_kemisans', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 50)->unique();
                $table->timestamps();
            });
        }

        // ---------- Supplier ----------
        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 120)->unique();
                // Kode supplier auto "SUP-001", "SUP-002", dst.
                $table->string('kode', 20)->unique();
                $table->string('telepon', 30)->nullable();
                $table->string('alamat', 255)->nullable();
                $table->timestamps();
            });
        }

        // ---------- Produk: kolom baru ----------
        // `kategori_id` dan `supplier_id` menggantikan teks bebas. Kolom teks
        // lama (kategori) tetap ada sebagai salinan tampilan supaya laporan
        // per-kategori dan pencarian lama tidak ikut rusak — tapi jadi
        // nullable, karena sumber kebenaran sekarang adalah relasinya.
        if (! Schema::hasColumn('stok', 'kategori_id')) {
            Schema::table('stok', function (Blueprint $table) {
                $table->foreignId('kategori_id')
                    ->nullable()
                    ->after('kategori')
                    ->constrained('kategoris')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('stok', 'supplier_id')) {
            Schema::table('stok', function (Blueprint $table) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('kategori_id')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            });
        }

        // Kolom teks `kategori` dibuat nullable: sumber kebenaran sekarang
        // adalah relasi `kategori_id`, jadi produk yang belum punya kategori
        // tetap bisa disimpan.
        if (! $this->kategoriBolehKosong()) {
            Schema::table('stok', function (Blueprint $table) {
                $table->string('kategori', 100)->nullable()->change();
            });
        }
    }

    /**
     * Apakah kolom `kategori` sudah boleh kosong?
     *
     * Dicek lewat attempt kecil yang dibungkus try: SQLite (dipakai saat
     * test di host) tidak mendukung ALTER COLUMN, dan pemeriksaan ini
     * tidak boleh menggagalkan migrasi.
     */
    private function kategoriBolehKosong(): bool
    {
        try {
            DB::table('stok')->insert([
                'barcode' => '__cek_nullable_kategori__',
                'nama_produk' => '__cek__',
                'kategori' => null,
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        DB::table('stok')->where('barcode', '__cek_nullable_kategori__')->delete();

        return true;
    }

    public function down(): void
    {
        Schema::table('stok', function (Blueprint $table) {
            $table->dropForeign(['kategori_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['kategori_id', 'supplier_id']);
        });

        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('satuan_kemisans');
        Schema::dropIfExists('satuans');
        Schema::dropIfExists('kategoris');
    }
};
