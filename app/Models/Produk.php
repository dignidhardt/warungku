<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produk';

    protected $fillable = [
        'barcode',
        'nama_produk',
        'kategori',
        'harga_beli',
        'harga_jual',
        'stok',
    ];
}