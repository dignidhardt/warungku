<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use App\Models\Stok;
use App\Models\TutupBuku;
use App\Services\LaporanService;
use App\Services\TutupBukuService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        LaporanService $laporan,
        TutupBukuService $tutupBuku
    ) {
        $hariIni = $laporan->transaksiHariIni();
        $ringkasanHariIni = $laporan->ringkasan($hariIni);

        // Angka bulan berjalan, bukan cuma hari ini. Pemilik warung jarang
        // hanya tertarik omzet harian — yang penting kondisi usaha sebulan.
        $labaRugi = $laporan->labaRugi(now()->month, now()->year);

        // Nilai persediaan dihitung dari harga beli: itu uang yang tertahan
        // di rak, bukan yang sudah jadi untung.
        $nilaiPersediaan = (int) Stok::query()->sum(
            DB::raw('stok * harga_beli')
        );

        $stokMenipis = Stok::whereColumn('stok', '<=', 'stok_minimal')
            ->where('stok', '>', 0)
            ->orderBy('stok')
            ->get();

        $stokHabis = Stok::where('stok', '<=', 0)->orderBy('nama_produk')->get();

        // Saran pembelian: barang yang stoknya menipis, diurutkan berdasarkan
        // seberapa sering terjual. Ini yang membantu pemilik memutuskan
        // apa yang perlu dibeli kembali.
        $produkTerlaris = $laporan->produkTerlaris(
            $laporan->transaksiPeriode(now()->month, now()->year),
            100
        );
        $terlarisMap = $produkTerlaris->keyBy('nama');

        $saranPembelian = $stokMenipis
            ->map(function ($produk) use ($terlarisMap) {
                $terlaris = $terlarisMap->get($produk->nama_produk);

                return [
                    'produk' => $produk,
                    'terjual' => $terlaris['qty'] ?? 0,
                    'saran' => max($produk->stok_minimal * 2, 10),
                ];
            })
            ->sortByDesc('terjual')
            ->values();

        $tutupBukuTerakhir = TutupBuku::latest('tanggal')->first();

        return view('home', [
            'totalProduk' => Stok::count(),
            'transaksiHariIni' => $ringkasanHariIni['jumlah_transaksi'],
            'totalPendapatanHariIni' => $ringkasanHariIni['omzet'],
            'labaHariIni' => $ringkasanHariIni['laba'],
            'stokMenipis' => $stokMenipis->take(5),
            'stokHabis' => $stokHabis,
            'totalStokMenipis' => $stokMenipis->count(),
            'transaksiTerakhir' => $hariIni->take(5),
            'produkTerlaris' => $laporan->produkTerlaris($hariIni, 5),
            'labaRugi' => $labaRugi,
            'nilaiPersediaan' => $nilaiPersediaan,
            'saranPembelian' => $saranPembelian,
            'pengeluaranBulanIni' => (int) Pengeluaran::whereBetween('tanggal', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])->sum('jumlah'),
            'kasSeharusnyaHariIni' => $tutupBuku->kasSeharusnya(Carbon::today()),
            'tutupBukuTerakhir' => $tutupBukuTerakhir,
            'totalTransaksiBulanIni' => $labaRugi['jumlah_transaksi'],
        ]);
    }
}
