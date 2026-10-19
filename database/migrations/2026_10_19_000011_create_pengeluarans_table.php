<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Kas keluar non-pembelian.
         *
         * `jenis` memisahkan dua hal yang di dokumen perancangan disebut
         * sebagai masalah utama: uang usaha dan uang pribadi pemilik. Prive
         * BUKAN biaya usaha, jadi tidak boleh mengurangi laba — hanya
         * mengurangi kas. Menggabungkannya membuat laporan laba terlihat lebih
         * buruk dari kenyataan dan pemilik salah menilai usahanya.
         */
        Schema::create('pengeluarans', function (Blueprint $table) {
            $table->id();
            // operasional | prive | lain
            $table->string('jenis', 20)->default('operasional')->index();
            $table->string('kategori', 60)->index();
            $table->date('tanggal')->index();
            $table->unsignedBigInteger('jumlah');
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['jenis', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluarans');
    }
};
