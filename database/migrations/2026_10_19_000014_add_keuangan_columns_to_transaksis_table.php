<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom baru pada transaksis: diskon, metode bayar, dan tutup buku.
     *
     * Semua langkah dijaga `hasColumn` karena MySQL tidak bisa me-rollback DDL:
     * kegagalan di tengah akan meninggalkan kolom setengah jadi dan migrasi
     * tidak bisa diulang (lihat kasus 2026_10_19_000002).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('transaksis', 'diskon')) {
            Schema::table('transaksis', function (Blueprint $table) {
                // Diskon dalam rupiah, bukan persen: nilai akhirnya yang
                // disimpan agar laporan tidak perlu menghitung ulang.
                $table->unsignedBigInteger('diskon')->default(0)->after('detail_produk');
            });
        }

        if (! Schema::hasColumn('transaksis', 'metode_bayar')) {
            Schema::table('transaksis', function (Blueprint $table) {
                // tunai | qris | transfer | debit
                $table->string('metode_bayar', 20)->default('tunai')->after('bayar');
            });
        }

        if (! Schema::hasColumn('transaksis', 'tutup_buku_id')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->foreignId('tutup_buku_id')->nullable()->after('user_id')
                    ->constrained('tutup_bukus')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('transaksis', 'dikembalikan')) {
            Schema::table('transaksis', function (Blueprint $table) {
                // Penanda nota yang sebagian atau seluruh barangnya diretur,
                // berbeda dari pembatalan: retur tidak menghapus penjualan,
                // hanya mengoreksi sebagian.
                $table->unsignedBigInteger('dikembalikan')->default(0)->after('kembali');
            });
        }
    }

    public function down(): void
    {
        foreach (['tutup_buku_id', 'dikembalikan', 'metode_bayar', 'diskon'] as $kolom) {
            if (Schema::hasColumn('transaksis', $kolom)) {
                Schema::table('transaksis', function (Blueprint $table) use ($kolom) {
                    if ($kolom === 'tutup_buku_id') {
                        $table->dropForeign(['tutup_buku_id']);
                    }
                    $table->dropColumn($kolom);
                });
            }
        }
    }
};
