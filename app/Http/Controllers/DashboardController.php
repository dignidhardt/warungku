<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Services\LaporanService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, LaporanService $laporan)
    {
        $hariIni = $laporan->transaksiHariIni();
        $ringkasanHariIni = $laporan->ringkasan($hariIni);

        return view('home', [
            'totalProduk' => Stok::count(),
            'transaksiHariIni' => $ringkasanHariIni['jumlah_transaksi'],
            'totalPendapatanHariIni' => $ringkasanHariIni['omzet'],
            'labaHariIni' => $ringkasanHariIni['laba'],
            'stokMenipis' => Stok::whereColumn('stok', '<=', 'stok_minimal')
                ->orderBy('stok')
                ->limit(5)
                ->get(),
            'stokHabis' => Stok::where('stok', '<=', 0)->count(),
            'transaksiTerakhir' => $hariIni->take(5),
            'produkTerlaris' => $laporan->produkTerlaris($hariIni, 5),
        ]);
    }
}
