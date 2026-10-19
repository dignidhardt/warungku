<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pembelian ke supplier. Ini sisi "uang keluar" yang selama ini tidak
        // tercatat: sebelumnya stok hanya bisa ditambah manual tanpa nilai
        // rupiah, sehingga modal yang dipakai untuk persediaan tidak terlacak.
        Schema::create('pembelians', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pembelian', 64)->unique();
            $table->string('supplier')->nullable();
            $table->date('tanggal')->index();
            $table->unsignedBigInteger('total')->default(0);
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pembelian_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembelian_id')->constrained('pembelians')->cascadeOnDelete();
            $table->foreignId('stok_id')->constrained('stok')->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('harga_beli');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();

            $table->index(['pembelian_id', 'stok_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelian_items');
        Schema::dropIfExists('pembelians');
    }
};
