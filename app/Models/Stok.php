<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'kategori_id',
        'supplier_id',
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
     * Relasi ke kategori.
     *
     * NAMANYA BUKAN `kategori()` dengan sengaja. Kolom `kategori` (teks
     * lama) dan relasi `kategori` akan saling menimpa: Eloquent mencari
     * atribut `kategori` lebih dulu, jadi `$produk->kategori` mengembalikan
     * STRING kolom, bukan objek relasi — dan `kodeBarangOtomatis()` yang
     * menerima `?Kategori` akan menerima string dan melempar TypeError.
     *
     * Jadi relasinya dinamai `kategoriRelasi()`.
     */
    public function kategoriRelasi(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Nama kategori untuk ditampilkan.
     *
     * Preferensi ke relasi (data baru), dengan fallback ke kolom teks
     * `kategori` (data lama yang belum sempat ditautkan).
     */
    public function getNamaKategoriAttribute(): string
    {
        return $this->kategoriRelasi?->nama ?? ($this->kategori ?: '—');
    }

    /**
     * Nama supplier untuk ditampilkan.
     */
    public function getNamaSupplierAttribute(): string
    {
        return $this->supplier?->nama ?? '—';
    }

    /**
     * Kode Barang otomatis untuk produk dalam kategori tertentu.
     *
     * Format: "<Kode Barang kategori>-<nomor urut 3 digit>", mis. "AAA-001".
     * Nomor urut dihitung dari produk yang SUDAH ada di kategori itu, jadi
     * produk pertama di kategori "AAA" mendapat "AAA-001".
     *
     * Admin tetap boleh mengubah sendiri nilainya di form.
     */
    public static function kodeBarangOtomatis(?Kategori $kategori): string
    {
        if (! $kategori) {
            return '';
        }

        $awalan = $kategori->awalanKodeBarang();

        $tertinggi = 0;

        $produk = static::query()
            ->where('kategori_id', $kategori->id)
            ->where('barcode', 'like', $awalan.'-%')
            ->get(['barcode']);

        foreach ($produk as $item) {
            if (preg_match('/'.preg_quote($awalan, '/').'-(\d+)$/', (string) $item->barcode, $cocok)) {
                $tertinggi = max($tertinggi, (int) $cocok[1]);
            }
        }

        return $awalan.'-'.str_pad((string) ($tertinggi + 1), 3, '0', STR_PAD_LEFT);
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
