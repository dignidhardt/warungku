<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok', function (Blueprint $table) {
            $table->id();
            // Barcode unik: mencegah dua produk dengan kode sama yang membuat
            // pemindaian di kasir memilih barang yang salah.
            $table->string('barcode')->unique();
            $table->string('nama_produk');
            $table->string('kategori')->index();
            $table->unsignedInteger('harga_beli')->default(0);
            $table->unsignedInteger('harga_jual')->default(0);
            $table->integer('stok')->default(0);
            // Ambang batas peringatan stok menipis, dipakai di halaman stok.
            $table->unsignedInteger('stok_minimal')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok');
    }
};
