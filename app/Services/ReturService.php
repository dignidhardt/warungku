<?php

namespace App\Services;

use App\Models\Retur;
use App\Models\Stok;
use App\Models\StokMutasi;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Retur penjualan: pembeli mengembalikan sebagian barang.
 *
 * Beda dengan pembatalan: pembatalan membatalkan SELURUH nota dan menghapus
 * nilai penjualannya. Retur hanya mengoreksi sebagian — notasinya tetap ada,
 * karena penjualan itu memang terjadi; yang berubah hanya barang yang kembali.
 */
class ReturService
{
    /**
     * @param  array<int, array{id:int|string, qty:int}>  $items
     *
     * @throws RuntimeException
     */
    public function simpan(Transaksi $transaksi, array $items, string $alasan, ?User $oleh = null): Retur
    {
        if ($transaksi->sudah_dibatalkan) {
            throw new RuntimeException('Transaksi yang sudah dibatalkan tidak bisa diretur.');
        }

        if ($items === []) {
            throw new RuntimeException('Belum ada barang yang diretur.');
        }

        return DB::transaction(function () use ($transaksi, $items, $alasan, $oleh) {
            // Hitung qty yang sudah pernah diretur per produk, supaya total
            // retur tidak bisa melebihi yang dibeli.
            $sudahRetur = [];
            foreach ($transaksi->returs()->with('items')->get() as $returLama) {
                foreach ($returLama->items as $barisLama) {
                    $sudahRetur[(int) $barisLama->stok_id] = ($sudahRetur[(int) $barisLama->stok_id] ?? 0)
                        + (int) $barisLama->qty;
                }
            }

            $qtyDibeli = [];
            $hargaPerProduk = [];
            $namaPerProduk = [];
            foreach ($transaksi->items as $item) {
                $id = (int) ($item['id'] ?? 0);
                $qtyDibeli[$id] = ($qtyDibeli[$id] ?? 0) + (int) ($item['qty'] ?? 0);
                $hargaPerProduk[$id] = (int) ($item['harga'] ?? 0);
                $namaPerProduk[$id] = $item['nama'] ?? 'Tanpa nama';
            }

            $total = 0;
            $baris = [];
            $kembaliKeStok = [];

            foreach ($items as $item) {
                $id = (int) ($item['id'] ?? 0);
                $qty = (int) ($item['qty'] ?? 0);

                if (! isset($qtyDibeli[$id])) {
                    throw new RuntimeException('Produk yang diretur tidak ada di nota ini.');
                }

                if ($qty < 1) {
                    throw new RuntimeException('Jumlah retur tidak valid.');
                }

                $sisa = $qtyDibeli[$id] - ($sudahRetur[$id] ?? 0);

                if ($qty > $sisa) {
                    throw new RuntimeException(
                        "Jumlah retur '{$namaPerProduk[$id]}' melebihi sisa yang bisa diretur ({$sisa})."
                    );
                }

                $subtotal = $hargaPerProduk[$id] * $qty;
                $total += $subtotal;

                $baris[] = [
                    'stok_id' => $id,
                    'nama_produk' => $namaPerProduk[$id],
                    'qty' => $qty,
                    'harga' => $hargaPerProduk[$id],
                    'subtotal' => $subtotal,
                ];

                $kembaliKeStok[] = [$id, $qty];
            }

            $retur = Retur::create([
                'nomor_retur' => $this->nomorBaru(),
                'transaksi_id' => $transaksi->id,
                'tanggal' => now(),
                'total' => $total,
                'alasan' => $alasan,
                'user_id' => $oleh?->id,
            ]);

            $retur->items()->createMany($baris);

            foreach ($kembaliKeStok as [$id, $qty]) {
                $produk = Stok::whereKey($id)->lockForUpdate()->first();

                if (! $produk) {
                    continue;
                }

                $produk->stok += $qty;
                $produk->save();

                StokMutasi::create([
                    'stok_id' => $produk->id,
                    'jenis' => StokMutasi::JENIS_MASUK,
                    'jumlah' => $qty,
                    'stok_sesudah' => $produk->stok,
                    'keterangan' => 'Retur '.$retur->nomor_retur.' ('.$transaksi->nomor_transaksi.')',
                    'user_id' => $oleh?->id,
                ]);
            }

            // Akumulasi nilai retur dipakai laporan untuk mengurangi omzet.
            $transaksi->increment('dikembalikan', $total);

            return $retur->load('items');
        });
    }

    private function nomorBaru(): string
    {
        return 'RTR-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(6));
    }
}
