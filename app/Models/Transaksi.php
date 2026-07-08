<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksis';

    protected $fillable = [
        'nomor_transaksi',
        'detail_produk',
        'grand_total',
        'bayar',
        'kembali'
    ];

    // Menggunakan fungsi casts() menggantikan properti $casts lama jika versinya bentrok
    protected function casts(): array
    {
        return [
            'detail_produk' => 'array',
        ];
    }
}