<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokMutasi extends Model
{
    public const JENIS_MASUK = 'masuk';

    public const JENIS_KELUAR = 'keluar';

    public const JENIS_PENYESUAIAN = 'penyesuaian';

    protected $table = 'stok_mutasis';

    protected $fillable = [
        'stok_id',
        'jenis',
        'jumlah',
        'stok_sesudah',
        'keterangan',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'stok_sesudah' => 'integer',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Stok::class, 'stok_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Label arah mutasi untuk ditampilkan di riwayat stok.
     */
    public function getLabelAttribute(): string
    {
        return match ($this->jenis) {
            self::JENIS_MASUK => 'Masuk',
            self::JENIS_KELUAR => 'Keluar',
            default => 'Penyesuaian',
        };
    }
}
