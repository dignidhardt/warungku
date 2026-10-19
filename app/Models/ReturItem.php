<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturItem extends Model
{
    protected $table = 'retur_items';

    protected $fillable = [
        'retur_id',
        'stok_id',
        'nama_produk',
        'qty',
        'harga',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function retur(): BelongsTo
    {
        return $this->belongsTo(Retur::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Stok::class, 'stok_id');
    }
}
