<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catatan gerakan stok: menjawab "kenapa stok berubah?" yang tidak bisa
        // dijawab kalau hanya ada satu kolom `stok` di tabel produk.
        if (! Schema::hasTable('stok_mutasis')) {
            Schema::create('stok_mutasis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stok_id')->constrained('stok')->cascadeOnDelete();
                // masuk | keluar | penyesuaian
                $table->string('jenis', 20);
                // Jumlah selalu positif; arah ditentukan oleh `jenis`.
                $table->unsignedInteger('jumlah');
                $table->integer('stok_sesudah');
                $table->string('keterangan')->nullable();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();

                $table->index(['stok_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_mutasis');
    }
};
