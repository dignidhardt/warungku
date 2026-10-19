<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengeluaran extends Model
{
    public const JENIS_OPERASIONAL = 'operasional';

    public const JENIS_PRIVE = 'prive';

    public const JENIS_LAIN = 'lain';

    protected $table = 'pengeluarans';

    protected $fillable = [
        'jenis',
        'kategori',
        'tanggal',
        'jumlah',
        'keterangan',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Prive adalah pengambilan uang pribadi pemilik. Uangnya keluar dari kas,
     * tapi BUKAN biaya usaha, jadi tidak boleh mengurangi laba.
     */
    public function getBiayaUsahaAttribute(): bool
    {
        return $this->jenis !== self::JENIS_PRIVE;
    }

    public function getLabelJenisAttribute(): string
    {
        return match ($this->jenis) {
            self::JENIS_PRIVE => 'Prive (pribadi)',
            self::JENIS_LAIN => 'Lain-lain',
            default => 'Operasional',
        };
    }

    /**
     * Pilihan kategori untuk form. Daftar tetap supaya laporan bisa
     * dikelompokkan dengan rapi, bukan teks bebas yang bervariasi.
     */
    public static function daftarKategori(): array
    {
        return [
            'Listrik & air',
            'Transportasi',
            'Gaji karyawan',
            'Sewa tempat',
            'Perlengkapan toko',
            'Perawatan',
            'Komunikasi',
            'Lainnya',
        ];
    }
}
