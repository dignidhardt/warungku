<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Exports\LaporanExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class LaporanController extends Controller
{
    // Menampilkan halaman laporan
    public function index(Request $request)
    {
        $bulanSelected = $request->bulan ?? date('m');
        $tahunSelected = $request->tahun ?? date('Y');

        // Ambil daftar tahun dari database
        $daftarTahun = Transaksi::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'DESC')
            ->pluck('year');

        // Jika database kosong
        if ($daftarTahun->isEmpty()) {
            $daftarTahun = collect([date('Y')]);
        }

        // Jika user memilih filter bulan & tahun
        if ($request->filled('bulan') && $request->filled('tahun')) {

            $laporan = Transaksi::whereMonth('created_at', $bulanSelected)
                ->whereYear('created_at', $tahunSelected)
                ->latest()
                ->get();

        } else {

            // Default: hanya transaksi hari ini
            $laporan = Transaksi::whereDate('created_at', Carbon::today())
                ->latest()
                ->get();
        }

        // Total pendapatan hari ini
        $totalHariIni = Transaksi::whereDate('created_at', Carbon::today())
            ->sum('grand_total');

        return view('layouts.laporan', compact(
            'laporan',
            'totalHariIni',
            'bulanSelected',
            'tahunSelected',
            'daftarTahun'
        ));
    }

    // Export Excel
    public function exportExcel(Request $request)
    {
        $bulan = $request->bulan ?? date('m');
        $tahun = $request->tahun ?? date('Y');

        return Excel::download(
            new LaporanExport($bulan, $tahun),
            'laporan_transaksi.xlsx'
        );
    }
}