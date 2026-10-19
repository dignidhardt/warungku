<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `kas_se_should` harus bisa negatif.
     *
     * Nilai kas = penjualan tunai - retur - pengeluaran - prive. Kalau pengeluaran
     * hari itu lebih besar dari penjualan (mis. bayar listrik di tengah hari
     * belum ada omzet), hasilnya negatif: laci sedang minus. Kolom unsigned
     * akan menolak nilai itu dengan "Out of range value", jadi tutup buku
     * gagal disimpan tepat di hari yang paling butuh dicatat.
     *
     * `kas_fisik` tetap unsigned — uang fisik tidak mungkin negatif.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tutup_bukus', 'kas_seharusnya')) {
            return;
        }

        Schema::table('tutup_bukus', function (Blueprint $table) {
            $table->bigInteger('kas_seharusnya')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tutup_bukus', function (Blueprint $table) {
            $table->unsignedBigInteger('kas_se_should')->default(0)->change();
        });
    }
};
