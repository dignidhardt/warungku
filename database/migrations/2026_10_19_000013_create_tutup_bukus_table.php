<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tutup buku harian (cash opname): kasir menghitung uang fisik di akhir
        // hari, sistem membandingkan dengan uang yang seharusnya ada menurut
        // catatan. Selisihnya disimpan, bukan disembunyikan.
        Schema::create('tutup_bukus', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            // Uang tunai yang seharusnya ada: penjualan tunai - prive - pengeluaran tunai.
            // Signed (bisa negatif): kalau pengeluaran lebih besar dari penjualan
            // pada hari itu, laci sedang minus dan angka itu wajib tersimpan.
            $table->bigInteger('kas_seharusnya')->default(0);
            // Uang fisik yang dihitung kasir
            $table->unsignedBigInteger('kas_fisik')->default(0);
            // kas_fisik - kas_seharusnya, bisa negatif (kurang) atau positif (lebih)
            $table->bigInteger('selisih')->default(0);
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutup_bukus');
    }
};
