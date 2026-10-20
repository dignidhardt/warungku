<?php

use App\Models\Kategori;
use App\Models\Supplier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Backfill: isi `stok.kategori_id` dan `stok.supplier_id` dari kolom teks
 * lama, dan tambahkan `supplier_id` ke baris pembelian.
 *
 * Data yang sudah ada (mis. di server live) tidak boleh hilang hanya
 * karena kolom teksnya diganti relasi. Teks lama tetap disimpan sebagai
 * salinan tampilan supaya laporan per-kategori dan pencarian lama tidak
 * ikut rusak.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- Pembelian: supplier per item ----------
        // Supplier sekarang berasal dari produk yang dibeli, jadi satu
        // pembelian bisa berisi barang dari beberapa supplier berbeda.
        if (! Schema::hasColumn('pembelian_items', 'supplier_id')) {
            Schema::table('pembelian_items', function (Blueprint $table) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('stok_id')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            });
        }

        // ---------- Backfill kategori ----------
        $kategoriTeks = DB::table('stok')
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->pluck('kategori');

        $petaKategori = [];

        foreach ($kategoriTeks as $nama) {
            $nama = trim((string) $nama);

            if ($nama === '') {
                continue;
            }

            $kategori = Kategori::firstOrCreate(
                ['nama' => $nama],
                ['kode_barang' => $this->kodeKategoriBaru($nama)]
            );

            $petaKategori[$nama] = $kategori->id;
        }

        foreach ($petaKategori as $nama => $id) {
            DB::table('stok')->where('kategori', $nama)->update(['kategori_id' => $id]);
        }

        // ---------- Backfill supplier ----------
        // Sumbernya: kolom `supplier` pada pembelian yang sudah ada.
        $supplierTeks = DB::table('pembelians')
            ->whereNotNull('supplier')
            ->where('supplier', '!=', '')
            ->distinct()
            ->pluck('supplier');

        foreach ($supplierTeks as $nama) {
            $nama = trim((string) $nama);

            if ($nama === '') {
                continue;
            }

            $supplier = Supplier::create([
                'nama' => $nama,
                'kode' => $this->kodeSupplierBaru(),
            ]);

            // Sambungkan pembelian lama ke supplier barunya.
            $pembelianIds = DB::table('pembelians')
                ->where('supplier', $nama)
                ->pluck('id');

            foreach ($pembelianIds as $pembelianId) {
                $stokIds = DB::table('pembelian_items')
                    ->where('pembelian_id', $pembelianId)
                    ->pluck('stok_id');

                DB::table('pembelian_items')
                    ->where('pembelian_id', $pembelianId)
                    ->whereIn('stok_id', $stokIds)
                    ->update(['supplier_id' => $supplier->id]);
            }

            // Produk yang ikut/barang dari supplier ini ikut tertaut.
            DB::table('stok')
                ->whereIn('id', $stokIds)
                ->whereNull('supplier_id')
                ->update(['supplier_id' => $supplier->id]);
        }
    }

    public function down(): void
    {
        Schema::table('pembelian_items', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }

    /**
     * Kode Barang 3 karakter untuk kategori lama. Coba 3 huruf pertama
     * dari nama (dibersihkan dari karakter non-alphanumeric); kalau
     * bentrok atau tidak menghasilkan 3 karakter, pakai urutan AAA, AAB, ...
     */
    private function kodeKategoriBaru(string $nama): string
    {
        $huruf = Str::upper(Str::of($nama)->replaceMatches('/[^A-Za-z0-9]/', '')->substr(0, 3));

        if (strlen($huruf) === 3 && ! Kategori::where('kode_barang', $huruf)->exists()) {
            return $huruf;
        }

        return $this->kodeKategoriBerikutnya();
    }

    /**
     * Urutan AAA, AAB, AAC, ... untuk kode kategori.
     */
    private function kodeKategoriBerikutnya(): string
    {
        $huruf = range('A', 'Z');

        for ($a = 0; $a < 26; $a++) {
            for ($b = 0; $b < 26; $b++) {
                for ($c = 0; $c < 26; $c++) {
                    $kode = $huruf[$a].$huruf[$b].$huruf[$c];

                    if (! Kategori::where('kode_barang', $kode)->exists()) {
                        return $kode;
                    }
                }
            }
        }

        return 'ZZ'.random_int(0, 9);
    }

    /**
     * Kode supplier auto: SUP-001, SUP-002, dst.
     */
    private function kodeSupplierBaru(): string
    {
        $tertinggi = 0;

        foreach (Supplier::pluck('kode') as $kode) {
            if (preg_match('/^SUP-(\d+)$/', (string) $kode, $cocok)) {
                $tertinggi = max($tertinggi, (int) $cocok[1]);
            }
        }

        return 'SUP-'.str_pad((string) ($tertinggi + 1), 3, '0', STR_PAD_LEFT);
    }
};
