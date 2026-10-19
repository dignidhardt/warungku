<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stok extends Model
{
    use HasFactory;

    protected $table = 'stok';

    protected $fillable = [
        'barcode',
        'nama_produk',
        'kategori',
        'harga_beli',
        'harga_jual',
        'stok',
        'stok_minimal',
    ];

    protected function casts(): array
    {
        return [
            'harga_beli' => 'integer',
            'harga_jual' => 'integer',
            'stok' => 'integer',
            'stok_minimal' => 'integer',
        ];
    }

    public function mutasis(): HasMany
    {
        return $this->hasMany(StokMutasi::class, 'stok_id');
    }

    /**
     * Status stok untuk badge peringatan di halaman stok.
     */
    public function getStatusStokAttribute(): string
    {
        if ($this->stok <= 0) {
            return 'habis';
        }

        if ($this->stok <= $this->stok_minimal) {
            return 'menipis';
        }

        return 'aman';
    }

    /**
     * Margin per unit dalam rupiah, dipakai perhitungan laba laporan.
     */
    public function getMarginAttribute(): int
    {
        return $this->harga_jual - $this->harga_beli;
    }
}
