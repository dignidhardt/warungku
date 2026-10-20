<?php

namespace Tests;

use App\Models\Kategori;
use App\Models\Satuan;
use App\Models\SatuanKemasan;
use App\Models\Supplier;

/**
 * Helper untuk data master yang dipakai test.
 *
 * Produk sekarang WAJIB punya kategori dan supplier dari master data
 * (form produk memakai dropdown, bukan teks bebas). Test yang membuat
 * produk langsung lewat model tidak melewati form, jadi helper ini
 * memastikan master data yang dibutuhkan selalu ada.
 */
trait BuatMasterData
{
    protected function buatKategori(string $nama = 'Minuman', ?string $kode = null): Kategori
    {
        return Kategori::firstOrCreate(
            ['nama' => $nama],
            ['kode_barang' => $kode ?? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nama), 0, 3))]
        );
    }

    protected function buatSatuan(string $nama = 'pcs'): Satuan
    {
        return Satuan::firstOrCreate(['nama' => $nama]);
    }

    protected function buatSatuanKemasan(string $nama = 'karung'): SatuanKemasan
    {
        return SatuanKemasan::firstOrCreate(['nama' => $nama]);
    }

    protected function buatSupplier(string $nama = 'Toko Besar'): Supplier
    {
        return Supplier::firstOrCreate(
            ['nama' => $nama],
            ['kode' => Supplier::kodeBerikutnya()]
        );
    }
}
