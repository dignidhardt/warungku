<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kategori produk.
 *
 * Dipakai sebagai isi dropdown "Kategori" di form produk, supaya tidak
 * ada lagi input teks bebas yang bisa salah eja (mis. "makanan" vs
 * "Makanan") dan memecah laporan per-kategori.
 */
class Kategori extends Model
{
    protected $table = 'kategoris';

    protected $fillable = [
        'nama',
        'kode_barang',
    ];

    public function produk(): HasMany
    {
        return $this->hasMany(Stok::class, 'kategori_id');
    }

    /**
     * Prefiks Kode Barang produk belongingkat ini, mis. "AAA-001".
     */
    public function awalanKodeBarang(): string
    {
        return strtoupper((string) $this->kode_barang);
    }
}
