<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-satuan untuk sembako.
     *
     * Model yang dipakai sengaja sederhana: satu produk punya SATU satuan dasar
     * (pcs / kg / liter) dan boleh punya satuan kemasan dengan faktor konversi.
     * Contoh: beras satuan dasar "kg", kemasan "karung" faktor 25. Stok selalu
     * disimpan dalam satuan dasar supaya penjumlahan dan pengurangan tetap
     * benar; konversi hanya terjadi saat input dan tampilan.
     *
     * Alternatif "satu produk banyak SKU" ditolak karena membuat laporan
     * terlaris dan nilai persediaan terpecah ke banyak baris.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('stok', 'satuan')) {
            Schema::table('stok', function (Blueprint $table) {
                // Satuan dasar untuk stok. Stok di kolom `stok` selalu dalam
                // satuan ini.
                $table->string('satuan', 20)->default('pcs')->after('nama_produk');
            });
        }

        if (! Schema::hasColumn('stok', 'satuan_kemasan')) {
            Schema::table('stok', function (Blueprint $table) {
                // Nama kemasan (karung, dus, renceng). NULL = tidak ada kemasan.
                $table->string('satuan_kemasan', 20)->nullable()->after('satuan');
            });
        }

        if (! Schema::hasColumn('stok', 'isi_kemasan')) {
            Schema::table('stok', function (Blueprint $table) {
                // Berapa satuan dasar dalam satu kemasan. NULL = tanpa kemasan.
                $table->unsignedInteger('isi_kemasan')->nullable()->after('satuan_kemasan');
            });
        }
    }

    public function down(): void
    {
        foreach (['satuan', 'satuan_kemasan', 'isi_kemasan'] as $kolom) {
            if (Schema::hasColumn('stok', $kolom)) {
                Schema::table('stok', function (Blueprint $table) use ($kolom) {
                    $table->dropColumn($kolom);
                });
            }
        }
    }
};
