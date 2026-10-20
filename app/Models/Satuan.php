<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satuan dasar produk (pcs, kg, liter, ...).
 *
 * Dipakai sebagai isi dropdown "Satuan" di form produk. Sebelumnya daftar
 * satuan ditulis sebagai konstanta di model Stok, sehingga menambah
 * satuan baru harus mengubah kode aplikasi.
 */
class Satuan extends Model
{
    protected $table = 'satuans';

    protected $fillable = [
        'nama',
    ];

    public function produk(): HasMany
    {
        return $this->hasMany(Stok::class, 'satuan');
    }
}
