<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transaksi')->unique();
            $table->text('detail_produk'); // Menyimpan data JSON keranjang belanja
            $table->integer('grand_total');
            $table->integer('bayar');
            $table->integer('kembali');
            $table->timestamps(); // Mencatat tanggal & jam otomatis untuk laporan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};