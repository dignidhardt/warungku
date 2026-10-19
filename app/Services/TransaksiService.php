<?php

namespace App\Services;

use App\Models\Stok;
use App\Models\StokMutasi;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Satu-satunya tempat yang boleh mengubah stok dan membuat transaksi.
 *
 * Alasan dipisah ke service: pemotongan stok dan penyimpanan nota harus satu
 * paket. Kalau dipisah di controller (seperti kode sebelumnya), kegagalan
 * menyimpan nota meninggalkan stok yang sudah terlanjur berkurang tanpa bukti
 * penjualan.
 */
class TransaksiService
{
    /**
     * Simpan penjualan: validasi stok, potong stok, catat mutasi, buat nota.
     *
     * @param  array<int, array{id:int|string, qty:int}>  $items
     * @param  int  $diskon  potongan dalam rupiah (bukan persen)
     *
     * @throws RuntimeException saat stok tidak cukup atau data tidak valid
     */
    public function simpanPenjualan(
        array $items,
        int $bayar,
        ?User $kasir = null,
        int $diskon = 0,
        string $metodeBayar = Transaksi::METODE_TUNAI
    ): Transaksi {
        if ($items === []) {
            throw new RuntimeException('Keranjang masih kosong.');
        }

        if ($diskon < 0) {
            throw new RuntimeException('Diskon tidak boleh negatif.');
        }

        $items = $this->gabungkanItemSama($items);

        return DB::transaction(function () use ($items, $bayar, $kasir, $diskon, $metodeBayar) {
            $subtotalBelanja = 0;
            $rincian = [];
            $produkTerkunci = [];

            // Kunci baris stok sebelum dibaca supaya dua kasir yang menjual
            // barang sama pada waktu bersamaan tidak bisa lolos validasi
            // keduanya lalu membuat stok minus.
            $ids = array_column($items, 'id');
            $terkunci = Stok::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $produk = $terkunci->get((int) $item['id']);

                if (! $produk) {
                    throw new RuntimeException('Produk tidak ditemukan.');
                }

                $qty = (int) $item['qty'];

                if ($qty < 1) {
                    throw new RuntimeException("Jumlah untuk '{$produk->nama_produk}' tidak valid.");
                }

                if ($produk->stok < $qty) {
                    throw new RuntimeException(
                        "Stok '{$produk->nama_produk}' tidak mencukupi. "
                        .'Sisa: '.$produk->labelStok($produk->stok).'.'
                    );
                }

                // Harga diambil dari database, bukan dari kiriman browser, agar
                // harga tidak bisa diubah lewat request.
                $subtotal = $produk->harga_jual * $qty;
                $subtotalBelanja += $subtotal;

                $rincian[] = [
                    'id' => $produk->id,
                    'nama' => $produk->nama_produk,
                    'barcode' => $produk->barcode,
                    'satuan' => $produk->satuan,
                    'harga' => $produk->harga_jual,
                    // Harga beli disimpan per nota supaya laba historis tidak
                    // berubah ketika harga beli produk diperbarui nanti.
                    'harga_beli' => $produk->harga_beli,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                ];

                $produkTerkunci[] = [$produk, $qty];
            }

            // Diskon tidak boleh melebihi nilai belanja, kalau tidak grand_total
            // jadi negatif dan laporan omzet rusak.
            $diskon = min($diskon, $subtotalBelanja);
            $total = $subtotalBelanja - $diskon;

            // Untuk non-tunai, uang yang diserahkan dianggap pas: tidak ada
            // kembalian karena pembayaran lewat QRIS/transfer/debit.
            if ($metodeBayar !== Transaksi::METODE_TUNAI) {
                $bayar = $total;
            }

            if ($bayar < $total) {
                throw new RuntimeException('Uang pembayaran kurang dari total belanja.');
            }

            $transaksi = Transaksi::create([
                'user_id' => $kasir?->id,
                'nomor_transaksi' => $this->nomorBaru(),
                'detail_produk' => $rincian,
                'diskon' => $diskon,
                'grand_total' => $total,
                'bayar' => $bayar,
                'kembali' => $bayar - $total,
                'metode_bayar' => $metodeBayar,
            ]);

            foreach ($produkTerkunci as [$produk, $qty]) {
                $produk->stok -= $qty;
                $produk->save();

                StokMutasi::create([
                    'stok_id' => $produk->id,
                    'jenis' => StokMutasi::JENIS_KELUAR,
                    'jumlah' => $qty,
                    'stok_sesudah' => $produk->stok,
                    'keterangan' => 'Penjualan '.$transaksi->nomor_transaksi,
                    'user_id' => $kasir?->id,
                ]);
            }

            return $transaksi;
        });
    }

    /**
     * Batalkan transaksi: kembalikan stok, catat mutasi, tandai batal.
     * Riwayat tidak dihapus supaya tetap bisa diaudit.
     */
    public function batalkan(Transaksi $transaksi, User $oleh): Transaksi
    {
        if ($transaksi->sudah_dibatalkan) {
            throw new RuntimeException('Transaksi ini sudah dibatalkan sebelumnya.');
        }

        return DB::transaction(function () use ($transaksi, $oleh) {
            foreach ($transaksi->items as $item) {
                $produk = Stok::whereKey($item['id'] ?? 0)->lockForUpdate()->first();

                // Produk bisa saja sudah dihapus; pembatalan tetap jalan tapi
                // stoknya tidak bisa dikembalikan.
                if (! $produk) {
                    continue;
                }

                $qty = (int) ($item['qty'] ?? 0);
                $produk->stok += $qty;
                $produk->save();

                StokMutasi::create([
                    'stok_id' => $produk->id,
                    'jenis' => StokMutasi::JENIS_MASUK,
                    'jumlah' => $qty,
                    'stok_sesudah' => $produk->stok,
                    'keterangan' => 'Pembatalan '.$transaksi->nomor_transaksi,
                    'user_id' => $oleh->id,
                ]);
            }

            $transaksi->update([
                'dibatalkan_pada' => now(),
                'dibatalkan_oleh' => $oleh->id,
            ]);

            return $transaksi->refresh();
        });
    }

    /**
     * Penyesuaian stok manual (stok opname): menyimpan angka akhir yang benar
     * beserta alasannya, bukan menimpa kolom tanpa jejak.
     */
    public function sesuaikanStok(Stok $produk, int $stokBaru, string $keterangan, ?User $oleh = null): Stok
    {
        if ($stokBaru < 0) {
            throw new RuntimeException('Stok tidak boleh negatif.');
        }

        return DB::transaction(function () use ($produk, $stokBaru, $keterangan, $oleh) {
            $produk = Stok::whereKey($produk->id)->lockForUpdate()->firstOrFail();
            $selisih = $stokBaru - $produk->stok;

            if ($selisih === 0) {
                return $produk;
            }

            $produk->stok = $stokBaru;
            $produk->save();

            StokMutasi::create([
                'stok_id' => $produk->id,
                'jenis' => StokMutasi::JENIS_PENYESUAIAN,
                'jumlah' => abs($selisih),
                'stok_sesudah' => $stokBaru,
                'keterangan' => $keterangan,
                'user_id' => $oleh?->id,
            ]);

            return $produk;
        });
    }

    /**
     * Penambahan stok (barang datang).
     */
    public function tambahStok(Stok $produk, int $jumlah, string $keterangan, ?User $oleh = null): Stok
    {
        if ($jumlah < 1) {
            throw new RuntimeException('Jumlah penambahan harus lebih dari nol.');
        }

        return DB::transaction(function () use ($produk, $jumlah, $keterangan, $oleh) {
            $produk = Stok::whereKey($produk->id)->lockForUpdate()->firstOrFail();
            $produk->stok += $jumlah;
            $produk->save();

            StokMutasi::create([
                'stok_id' => $produk->id,
                'jenis' => StokMutasi::JENIS_MASUK,
                'jumlah' => $jumlah,
                'stok_sesudah' => $produk->stok,
                'keterangan' => $keterangan,
                'user_id' => $oleh?->id,
            ]);

            return $produk;
        });
    }

    /**
     * Nomor nota berbasis waktu + acak. `time()` saja bentrok ketika dua
     * transaksi terjadi pada detik yang sama (unique index menolaknya).
     */
    private function nomorBaru(): string
    {
        return 'TRX-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(6));
    }

    /**
     * Gabungkan baris dengan produk sama supaya validasi stok menghitung total
     * kebutuhan, bukan per baris (kalau tidak, 2 baris x 3 pcs bisa lolos
     * padahal stok hanya 4).
     *
     * @param  array<int, array{id:int|string, qty:int}>  $items
     * @return array<int, array{id:int, qty:int}>
     */
    private function gabungkanItemSama(array $items): array
    {
        $hasil = [];

        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);

            if ($id < 1 || $qty < 1) {
                throw new RuntimeException('Data keranjang tidak valid.');
            }

            $hasil[$id] = ($hasil[$id] ?? 0) + $qty;
        }

        return array_map(
            fn (int $id, int $qty) => ['id' => $id, 'qty' => $qty],
            array_keys($hasil),
            array_values($hasil)
        );
    }
}
