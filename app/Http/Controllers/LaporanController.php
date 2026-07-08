<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Exports\LaporanExport;
use Maatwebsite\Excel\Facades\Excel;

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

        // Ambil data transaksi sesuai filter
        $laporan = Transaksi::whereMonth('created_at', $bulanSelected)
            ->whereYear('created_at', $tahunSelected)
            ->get();

        // Hitung total omset
        $totalOmset = $laporan->sum('grand_total');

        return view('layouts.laporan', compact(
            'laporan',
            'bulanSelected',
            'tahunSelected',
            'totalOmset',
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