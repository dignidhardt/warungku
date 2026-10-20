<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satuan kemasan (karung, dus, renceng, ...).
 *
 * Berbeda dari `Satuan`, satuan kemasan BUKAN satuan dasar: satu kemasan
 * berisi sejumlah satuan dasar (lihat `Stok::isi_kemasan`). Karena itu
 * kecocokannya tidak lewat foreign key, tapi lewat nama.
 */
class SatuanKemasan extends Model
{
    protected $table = 'satuan_kemisans';

    protected $fillable = [
        'nama',
    ];
}
