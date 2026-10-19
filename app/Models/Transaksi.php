<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    use HasFactory;

    public const METODE_TUNAI = 'tunai';

    public const METODE_QRIS = 'qris';

    public const METODE_TRANSFER = 'transfer';

    public const METODE_DEBIT = 'debit';

    protected $table = 'transaksis';

    protected $fillable = [
        'user_id',
        'tutup_buku_id',
        'nomor_transaksi',
        'detail_produk',
        'diskon',
        'grand_total',
        'bayar',
        'kembali',
        'metode_bayar',
        'dikembalikan',
        'dibatalkan_pada',
        'dibatalkan_oleh',
    ];

    protected function casts(): array
    {
        return [
            // Disimpan sebagai JSON oleh cast ini (satu tempat saja yang
            // meng-encode) sehingga tidak ada lagi double-encode.
            'detail_produk' => 'array',
            'diskon' => 'integer',
            'grand_total' => 'integer',
            'bayar' => 'integer',
            'kembali' => 'integer',
            'dikembalikan' => 'integer',
            'dibatalkan_pada' => 'datetime',
        ];
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pembatal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    public function tutupBuku(): BelongsTo
    {
        return $this->belongsTo(TutupBuku::class, 'tutup_buku_id');
    }

    public function returs(): HasMany
    {
        return $this->hasMany(Retur::class);
    }

    public function scopeAktif($query)
    {
        return $query->whereNull('dibatalkan_pada');
    }

    public function scopeTunai($query)
    {
        return $query->where('metode_bayar', self::METODE_TUNAI);
    }

    public function getSudahDibatalkanAttribute(): bool
    {
        return $this->dibatalkan_pada !== null;
    }

    public static function daftarMetode(): array
    {
        return [
            self::METODE_TUNAI => 'Tunai',
            self::METODE_QRIS => 'QRIS',
            self::METODE_TRANSFER => 'Transfer',
            self::METODE_DEBIT => 'Kartu debit',
        ];
    }

    public function getLabelMetodeAttribute(): string
    {
        return self::daftarMetode()[$this->metode_bayar] ?? ucfirst((string) $this->metode_bayar);
    }

    /**
     * Rincian item sebagai koleksi, aman untuk data lama yang masih berupa
     * string JSON (sebelum cast diperbaiki).
     */
    public function getItemsAttribute(): array
    {
        $detail = $this->detail_produk;

        if (is_string($detail)) {
            $detail = json_decode($detail, true) ?: [];
            // Data lama ter-encode dua kali; buka satu lapis lagi.
            if (is_string($detail)) {
                $detail = json_decode($detail, true) ?: [];
            }
        }

        return is_array($detail) ? $detail : [];
    }

    /**
     * Laba kotor transaksi: (harga jual - harga beli) x qty, dikurangi diskon.
     *
     * Diskon mengurangi laba karena omzet yang tercatat sudah dipotong diskon.
     * Mengabaikannya membuat laba terlihat lebih besar dari uang yang benar-benar
     * diterima.
     *
     * Butuh harga_beli per produk yang tidak disimpan di detail transaksi, jadi
     * pemanggil menyuplai peta harga beli (lihat LaporanService).
     */
    public function hitungLaba(array $hargaBeliPerProduk): int
    {
        $laba = 0;

        foreach ($this->items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $harga = (int) ($item['harga'] ?? 0);
            // Harga beli yang tersimpan di rincian lebih akurat daripada harga
            // beli produk saat ini, karena harga beli bisa berubah setelah nota
            // dibuat. Data lama belum punya kunci ini.
            $beli = (int) ($item['harga_beli'] ?? $hargaBeliPerProduk[$id] ?? 0);

            $laba += ($harga - $beli) * $qty;
        }

        return $laba - (int) $this->diskon;
    }

    /**
     * Total nilai barang yang sudah diretur (uang yang harus dikembalikan).
     */
    public function getTotalReturAttribute(): int
    {
        return (int) $this->returs()->sum('total');
    }
}
