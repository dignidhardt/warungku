<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Supplier barang dagangan.
 *
 * Setiap produk punya satu supplier. Supplier ini yang dipakai otomatis
 * di form Pembelian, jadi kasir tidak perlu mengetik nama supplier lagi.
 */
class Supplier extends Model
{
    protected $table = 'suppliers';

    protected $fillable = [
        'nama',
        'kode',
        'telepon',
        'alamat',
    ];

    public function produk(): HasMany
    {
        return $this->hasMany(Stok::class, 'supplier_id');
    }

    /**
     * Kode supplier berikutnya: SUP-001, SUP-002, dst.
     *
     * Dihitung dari kode yang sudah ada, bukan dari auto-increment id,
     * supaya penomoran tetap rapi walau ada baris yang dihapus.
     */
    public static function kodeBerikutnya(): string
    {
        $tertinggi = 0;

        foreach (self::pluck('kode') as $kode) {
            if (preg_match('/^SUP-(\d+)$/', (string) $kode, $cocok)) {
                $tertinggi = max($tertinggi, (int) $cocok[1]);
            }
        }

        return 'SUP-'.str_pad((string) ($tertinggi + 1), 3, '0', STR_PAD_LEFT);
    }
}
