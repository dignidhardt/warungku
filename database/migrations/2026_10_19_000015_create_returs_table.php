<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Retur penjualan: sebagian barang dari satu nota dikembalikan pembeli.
        //
        // Dibuat sebagai tabel sendiri, bukan kolom di `transaksis`, karena satu
        // nota bisa diretur lebih dari sekali dan tiap retur punya alasan serta
        // pelaku yang berbeda.
        Schema::create('returs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_retur', 64)->unique();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->dateTime('tanggal')->index();
            $table->unsignedBigInteger('total')->default(0);
            $table->string('alasan');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('retur_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_id')->constrained('returs')->cascadeOnDelete();
            $table->foreignId('stok_id')->nullable()->constrained('stok')->nullOnDelete();
            $table->string('nama_produk');
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('harga');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retur_items');
        Schema::dropIfExists('returs');
    }
};
