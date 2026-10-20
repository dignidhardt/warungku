<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\Stok;
use App\Models\StokMutasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pembelian barang ke supplier.
 *
 * Kenapa dipisah dari `TransaksiService::tambahStok`: menambah stok manual
 * tidak punya nilai rupiah, sehingga modal persediaan tidak terlacak. Pembelian
 * mencatat qty DAN harga beli, sekaligus memperbarui harga beli produk supaya
 * margin di laporan memakai harga terakhir.
 */
class PembelianService
{
    /**
     * @param  array<int, array{id:int|string, qty:int, harga_beli:int}>  $items
     *
     * @throws RuntimeException
     */
    public function simpan(
        array $items,
        string $tanggal,
        ?string $keterangan,
        ?User $oleh = null
    ): Pembelian {
        if ($items === []) {
            throw new RuntimeException('Belum ada barang yang dibeli.');
        }

        return DB::transaction(function () use ($items, $tanggal, $keterangan, $oleh) {
            $total = 0;
            $baris = [];
            $produkTerkunci = [];

            $terkunci = Stok::whereIn('id', array_column($items, 'id'))
                ->with('supplier')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $produk = $terkunci->get((int) $item['id']);

                if (! $produk) {
                    throw new RuntimeException('Produk tidak ditemukan.');
                }

                $qty = (int) $item['qty'];
                $hargaBeli = (int) $item['harga_beli'];

                if ($qty < 1) {
                    throw new RuntimeException("Jumlah beli '{$produk->nama_produk}' tidak valid.");
                }

                if ($hargaBeli < 0) {
                    throw new RuntimeException("Harga beli '{$produk->nama_produk}' tidak valid.");
                }

                $subtotal = $qty * $hargaBeli;
                $total += $subtotal;

                // Qty pembelian diinput dalam satuan kemasan bila produk punya
                // kemasan; konversi ke satuan dasar dilakukan di sini supaya
                // stok tetap konsisten satu satuan.
                $qtyDasar = $produk->keSatuanDasar($qty, $item['satuan'] ?? null);

                $baris[] = [
                    'stok_id' => $produk->id,
                    // Supplier diambil dari produk yang dibeli, bukan diketik
                    // manual: satu pembelian bisa berisi barang dari beberapa
                    // supplier berbeda.
                    'supplier_id' => $produk->supplier_id,
                    'qty' => $qtyDasar,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $subtotal,
                ];

                $produkTerkunci[] = [$produk, $qtyDasar, $hargaBeli];
            }

            // Ringkasan supplier untuk header pembelian: nama supplier yang
            // muncul di baris pembelian ini.
            $namaSupplier = collect($baris)
                ->map(fn ($b) => $terkunci->get($b['stok_id'])?->nama_supplier)
                ->filter(fn ($nama) => $nama && $nama !== '—')
                ->unique()
                ->implode(', ');

            $pembelian = Pembelian::create([
                'nomor_pembelian' => $this->nomorBaru(),
                'supplier' => $namaSupplier !== '' ? $namaSupplier : null,
                'tanggal' => $tanggal,
                'total' => $total,
                'keterangan' => $keterangan,
                'user_id' => $oleh?->id,
            ]);

            $pembelian->items()->createMany($baris);

            foreach ($produkTerkunci as [$produk, $qtyDasar, $hargaBeli]) {
                $produk->stok += $qtyDasar;
                // Harga beli produk ikut diperbarui: ini yang membuat margin di
                // laporan mengikuti harga kulakan terakhir, bukan harga lama.
                $produk->harga_beli = $hargaBeli;
                $produk->save();

                StokMutasi::create([
                    'stok_id' => $produk->id,
                    'jenis' => StokMutasi::JENIS_MASUK,
                    'jumlah' => $qtyDasar,
                    'stok_sesudah' => $produk->stok,
                    'keterangan' => 'Pembelian '.$pembelian->nomor_pembelian,
                    'user_id' => $oleh?->id,
                ]);
            }

            return $pembelian->load('items');
        });
    }

    /**
     * Batal pembelian: tarik kembali stoknya.
     *
     * Tidak dihapus supaya jejaknya tetap ada, tapi stok dikurangi. Kalau stok
     * sudah terlanjur terjual, pembatalan ditolak — lebih baik pemilik tahu
     * daripada stok jadi minus.
     */
    public function batalkan(Pembelian $pembelian, User $oleh): Pembelian
    {
        return DB::transaction(function () use ($pembelian, $oleh) {
            foreach ($pembelian->items as $item) {
                $produk = Stok::whereKey($item->stok_id)->lockForUpdate()->first();

                if (! $produk) {
                    continue;
                }

                if ($produk->stok < $item->qty) {
                    throw new RuntimeException(
                        "Stok '{$produk->nama_produk}' sudah terpakai, pembelian tidak bisa dibatalkan."
                    );
                }

                $produk->stok -= $item->qty;
                $produk->save();

                StokMutasi::create([
                    'stok_id' => $produk->id,
                    'jenis' => StokMutasi::JENIS_KELUAR,
                    'jumlah' => $item->qty,
                    'stok_sesudah' => $produk->stok,
                    'keterangan' => 'Pembatalan pembelian '.$pembelian->nomor_pembelian,
                    'user_id' => $oleh->id,
                ]);
            }

            $pembelian->delete();

            return $pembelian;
        });
    }

    private function nomorBaru(): string
    {
        return 'BLI-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(6));
    }
}
