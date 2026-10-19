<?php

namespace App\Services;

use App\Models\Stok;
use App\Models\Transaksi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Perhitungan laporan dipisah dari controller supaya angka yang tampil di
 * halaman, Excel, dan PDF dihitung dengan cara yang sama.
 */
class LaporanService
{
    /**
     * Ambil transaksi pada rentang tanggal tertentu.
     *
     * Memakai whereBetween, bukan whereMonth/whereYear/YEAR(), agar portabel
     * dan tetap memakai index created_at.
     */
    public function transaksiPeriode(int $bulan, int $tahun): Collection
    {
        [$mulai, $selesai] = $this->rentangBulan($bulan, $tahun);

        return Transaksi::aktif()
            ->whereBetween('created_at', [$mulai, $selesai])
            ->with('kasir')
            ->latest()
            ->get();
    }

    public function transaksiHariIni(): Collection
    {
        return Transaksi::aktif()
            ->whereBetween('created_at', [Carbon::today(), Carbon::today()->endOfDay()])
            ->with('kasir')
            ->latest()
            ->get();
    }

    /**
     * Ringkasan angka untuk satu periode.
     *
     * @return array{omzet:int, laba:int, jumlah_transaksi:int, jumlah_item:int}
     */
    public function ringkasan(Collection $transaksi): array
    {
        $hargaBeli = $this->petaHargaBeli();

        $omzet = 0;
        $laba = 0;
        $jumlahItem = 0;

        foreach ($transaksi as $row) {
            $omzet += (int) $row->grand_total;
            $laba += $row->hitungLaba($hargaBeli);

            foreach ($row->items as $item) {
                $jumlahItem += (int) ($item['qty'] ?? 0);
            }
        }

        return [
            'omzet' => $omzet,
            'laba' => $laba,
            'jumlah_transaksi' => $transaksi->count(),
            'jumlah_item' => $jumlahItem,
        ];
    }

    /**
     * Produk terlaris pada satu periode, diagregasi dari rincian nota.
     *
     * @return Collection<int, array{nama:string, qty:int, omzet:int}>
     */
    public function produkTerlaris(Collection $transaksi, int $batas = 10): Collection
    {
        $akumulasi = [];

        foreach ($transaksi as $row) {
            foreach ($row->items as $item) {
                $nama = $item['nama'] ?? 'Tanpa nama';
                $qty = (int) ($item['qty'] ?? 0);
                $subtotal = (int) ($item['subtotal'] ?? (($item['harga'] ?? 0) * $qty));

                $akumulasi[$nama] ??= ['nama' => $nama, 'qty' => 0, 'omzet' => 0];
                $akumulasi[$nama]['qty'] += $qty;
                $akumulasi[$nama]['omzet'] += $subtotal;
            }
        }

        return collect($akumulasi)
            ->sortByDesc('qty')
            ->take($batas)
            ->values();
    }

    /**
     * Daftar tahun yang benar-benar punya transaksi, untuk pilihan filter.
     *
     * Sengaja tidak memakai YEAR(created_at): fungsi itu MySQL-only, sehingga
     * query yang sama gagal di SQLite (dipakai saat test) maupun Postgres.
     */
    public function daftarTahun(): Collection
    {
        $tahun = Transaksi::query()
            ->select('created_at')
            ->orderByDesc('created_at')
            ->pluck('created_at')
            ->map(fn ($tanggal) => (int) Carbon::parse($tanggal)->year)
            ->unique()
            ->sortDesc()
            ->values();

        if ($tahun->isEmpty()) {
            return collect([(int) date('Y')]);
        }

        return $tahun;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rentangBulan(int $bulan, int $tahun): array
    {
        $mulai = Carbon::create($tahun, $bulan, 1)->startOfDay();

        return [$mulai, $mulai->copy()->endOfMonth()->endOfDay()];
    }

    /**
     * Peta id produk => harga beli, dipakai menghitung laba karena harga beli
     * tidak disimpan di rincian nota.
     *
     * @return array<int, int>
     */
    private function petaHargaBeli(): array
    {
        return Stok::query()
            ->select('id', 'harga_beli')
            ->pluck('harga_beli', 'id')
            ->map(fn ($harga) => (int) $harga)
            ->all();
    }
}
