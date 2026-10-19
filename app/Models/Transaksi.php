<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksis';

    protected $fillable = [
        'user_id',
        'nomor_transaksi',
        'detail_produk',
        'grand_total',
        'bayar',
        'kembali',
        'dibatalkan_pada',
        'dibatalkan_oleh',
    ];

    protected function casts(): array
    {
        return [
            // Disimpan sebagai JSON oleh cast ini (satu tempat saja yang
            // meng-encode) sehingga tidak ada lagi double-encode.
            'detail_produk' => 'array',
            'grand_total' => 'integer',
            'bayar' => 'integer',
            'kembali' => 'integer',
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

    public function scopeAktif($query)
    {
        return $query->whereNull('dibatalkan_pada');
    }

    public function getSudahDibatalkanAttribute(): bool
    {
        return $this->dibatalkan_pada !== null;
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
     * Laba kotor transaksi. Butuh harga_beli per produk yang tidak disimpan di
     * detail transaksi, jadi nilai ini hanya terisi kalau pemanggil menyuplai
     * peta harga beli (lihat LaporanController).
     */
    public function hitungLaba(array $hargaBeliPerProduk): int
    {
        $laba = 0;

        foreach ($this->items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $harga = (int) ($item['harga'] ?? 0);
            $beli = (int) ($hargaBeliPerProduk[$id] ?? 0);

            $laba += ($harga - $beli) * $qty;
        }

        return $laba;
    }
}
