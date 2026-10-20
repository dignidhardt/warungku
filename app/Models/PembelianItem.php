<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembelianItem extends Model
{
    protected $table = 'pembelian_items';

    protected $fillable = [
        'pembelian_id',
        'stok_id',
        'supplier_id',
        'qty',
        'harga_beli',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_beli' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Stok::class, 'stok_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
