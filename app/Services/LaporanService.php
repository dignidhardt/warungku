<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\Retur;
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
     * Ringkasan penjualan untuk satu periode.
     *
     * @return array{omzet:int, laba:int, jumlah_transaksi:int, jumlah_item:int, retur:int, diskon:int}
     */
    public function ringkasan(Collection $transaksi): array
    {
        $hargaBeli = $this->petaHargaBeli();

        $omzet = 0;
        $laba = 0;
        $jumlahItem = 0;
        $retur = 0;
        $diskon = 0;

        foreach ($transaksi as $row) {
            $omzet += (int) $row->grand_total;
            $laba += $row->hitungLaba($hargaBeli);
            $retur += (int) $row->dikembalikan;
            $diskon += (int) $row->diskon;

            foreach ($row->items as $item) {
                $jumlahItem += (int) ($item['qty'] ?? 0);
            }
        }

        return [
            'omzet' => $omzet,
            'laba' => $laba,
            'jumlah_transaksi' => $transaksi->count(),
            'jumlah_item' => $jumlahItem,
            'retur' => $retur,
            'diskon' => $diskon,
        ];
    }

    /**
     * Laporan laba rugi lengkap untuk satu periode.
     *
     * Susunannya:
     *   Omzet bersih      = penjualan - retur
     *   Harga pokok       = harga beli barang yang terjual
     *   Laba kotor        = omzet bersih - harga pokok
     *   Biaya operasional = pengeluaran jenis operasional (TANPA prive)
     *   Laba bersih       = laba kotor - biaya operasional
     *
     * Prive sengaja tidak masuk biaya: itu pengambilan uang pribadi pemilik,
     * bukan ongkos menjalankan usaha. Kalau dimasukkan, laba terlihat lebih
     * kecil dari kenyataan dan pemilik salah menilai usahanya.
     *
     * @return array<string, int|array>
     */
    public function labaRugi(int $bulan, int $tahun): array
    {
        [$mulai, $selesai] = $this->rentangBulan($bulan, $tahun);
        $transaksi = $this->transaksiPeriode($bulan, $tahun);

        $penjualan = (int) $transaksi->sum('grand_total');
        $retur = (int) $transaksi->sum('dikembalikan');
        $omzetBersih = $penjualan - $retur;

        // Harga pokok penjualan dihitung dari harga beli yang TERSIMPAN di
        // rincian nota, bukan harga beli produk saat ini. Kalau memakai harga
        // sekarang, laporan bulan lalu berubah setiap kali harga kulakan naik.
        $hpp = 0;
        foreach ($transaksi as $row) {
            foreach ($row->items as $item) {
                $hpp += (int) ($item['harga_beli'] ?? 0) * (int) ($item['qty'] ?? 0);
            }
        }

        $labaKotor = $omzetBersih - $hpp;

        $pengeluaran = Pengeluaran::whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])->get();

        $biayaOperasional = (int) $pengeluaran->where('jenis', '!=', Pengeluaran::JENIS_PRIVE)->sum('jumlah');
        $prive = (int) $pengeluaran->where('jenis', Pengeluaran::JENIS_PRIVE)->sum('jumlah');

        $labaBersih = $labaKotor - $biayaOperasional;

        // Rincian biaya per kategori untuk ditampilkan di laporan.
        $perKategori = $pengeluaran
            ->where('jenis', '!=', Pengeluaran::JENIS_PRIVE)
            ->groupBy('kategori')
            ->map(fn (Collection $rows) => (int) $rows->sum('jumlah'))
            ->sortDesc();

        $pembelian = (int) Pembelian::whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->sum('total');

        return [
            'penjualan' => $penjualan,
            'retur' => $retur,
            'omzet_bersih' => $omzetBersih,
            'hpp' => $hpp,
            'laba_kotor' => $labaKotor,
            'biaya_operasional' => $biayaOperasional,
            'prive' => $prive,
            'laba_bersih' => $labaBersih,
            'pembelian' => $pembelian,
            'per_kategori' => $perKategori,
            'jumlah_transaksi' => $transaksi->count(),
        ];
    }

    /**
     * Buku kas: seluruh uang masuk dan keluar pada satu periode, urut waktu.
     *
     * @return Collection<int, array{tanggal:string, keterangan:string, masuk:int, keluar:int, jenis:string}>
     */
    public function bukuKas(int $bulan, int $tahun): Collection
    {
        [$mulai, $selesai] = $this->rentangBulan($bulan, $tahun);
        $baris = collect();

        foreach ($this->transaksiPeriode($bulan, $tahun) as $trx) {
            $baris->push([
                'tanggal' => $trx->created_at->toDateTimeString(),
                'keterangan' => 'Penjualan '.$trx->nomor_transaksi.' ('.$trx->label_metode.')',
                'masuk' => (int) $trx->grand_total,
                'keluar' => 0,
                'jenis' => 'penjualan',
            ]);
        }

        foreach (Retur::whereBetween('tanggal', [$mulai, $selesai])->get() as $retur) {
            $baris->push([
                'tanggal' => $retur->tanggal->toDateTimeString(),
                'keterangan' => 'Retur '.$retur->nomor_retur,
                'masuk' => 0,
                'keluar' => (int) $retur->total,
                'jenis' => 'retur',
            ]);
        }

        foreach (Pembelian::whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])->get() as $beli) {
            $baris->push([
                'tanggal' => $beli->tanggal->toDateTimeString(),
                'keterangan' => 'Pembelian '.$beli->nomor_pembelian.($beli->supplier ? ' - '.$beli->supplier : ''),
                'masuk' => 0,
                'keluar' => (int) $beli->total,
                'jenis' => 'pembelian',
            ]);
        }

        foreach (Pengeluaran::whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])->get() as $keluar) {
            $baris->push([
                'tanggal' => $keluar->tanggal->toDateTimeString(),
                'keterangan' => $keluar->kategori.' - '.$keluar->label_jenis,
                'masuk' => 0,
                'keluar' => (int) $keluar->jumlah,
                'jenis' => $keluar->jenis,
            ]);
        }

        return $baris->sortBy('tanggal')->values();
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
     * Peta id produk => harga beli, cadangan bila rincian nota lama belum
     * menyimpan harga_beli.
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
