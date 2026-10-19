<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stok extends Model
{
    use HasFactory;

    public const SATUAN_PILIHAN = ['pcs', 'kg', 'gram', 'liter', 'ml', 'bungkus', 'botol', 'sachet'];

    protected $table = 'stok';

    protected $fillable = [
        'barcode',
        'nama_produk',
        'satuan',
        'satuan_kemasan',
        'isi_kemasan',
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
            'isi_kemasan' => 'integer',
        ];
    }

    public function mutasis(): HasMany
    {
        return $this->hasMany(StokMutasi::class, 'stok_id');
    }

    public function pembelianItems(): HasMany
    {
        return $this->hasMany(PembelianItem::class, 'stok_id');
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

    /**
     * Punya kemasan (mis. karung 25 kg) atau tidak.
     */
    public function getPunyaKemasanAttribute(): bool
    {
        return $this->satuan_kemasan !== null && (int) $this->isi_kemasan > 0;
    }

    /**
     * Ubah jumlah dalam satuan kemasan menjadi satuan dasar.
     *
     * Stok SELALU disimpan dalam satuan dasar. Kalau pemilik memasukkan
     * "2 karung" dan isi karungnya 25 kg, yang disimpan adalah 50.
     */
    public function keSatuanDasar(int|float $jumlah, ?string $tujuan = null): int
    {
        if ($tujuan === $this->satuan_kemasan && $this->punya_kemasan) {
            return (int) round($jumlah * (int) $this->isi_kemasan);
        }

        return (int) round($jumlah);
    }

    /**
     * Tampilan jumlah ideal: "50 kg (2 karung)" kalau ada kemasan.
     */
    public function labelStok(int $jumlah): string
    {
        $teks = $jumlah.' '.$this->satuan;

        if ($this->punya_kemasan && (int) $this->isi_kemasan > 0) {
            $karung = intdiv($jumlah, (int) $this->isi_kemasan);
            $sisa = $jumlah % (int) $this->isi_kemasan;

            if ($karung > 0) {
                $teks .= " ({$karung} {$this->satuan_kemasan}";
                $teks .= $sisa > 0 ? " + {$sisa} {$this->satuan})" : ')';
            }
        }

        return $teks;
    }
}
