<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Services\LaporanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(Request $request, LaporanService $laporan)
    {
        [$bulan, $tahun] = $this->periode($request);

        $transaksi = $laporan->transaksiPeriode($bulan, $tahun);

        return view('laporan.index', [
            'transaksi' => $transaksi,
            'ringkasan' => $laporan->ringkasan($transaksi),
            'hariIni' => $laporan->ringkasan($laporan->transaksiHariIni()),
            'produkTerlaris' => $laporan->produkTerlaris($transaksi),
            'bulanSelected' => $bulan,
            'tahunSelected' => $tahun,
            'daftarTahun' => $laporan->daftarTahun(),
            'namaBulan' => $this->namaBulan(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        [$bulan, $tahun] = $this->periode($request);

        return Excel::download(
            new LaporanExport($bulan, $tahun),
            "laporan-{$tahun}-{$bulan}.xlsx"
        );
    }

    public function exportPdf(Request $request, LaporanService $laporan)
    {
        [$bulan, $tahun] = $this->periode($request);

        $transaksi = $laporan->transaksiPeriode($bulan, $tahun);

        $pdf = Pdf::loadView('laporan.pdf', [
            'transaksi' => $transaksi,
            'ringkasan' => $laporan->ringkasan($transaksi),
            'produkTerlaris' => $laporan->produkTerlaris($transaksi),
            'bulan' => $this->namaBulan()[$bulan] ?? $bulan,
            'tahun' => $tahun,
            'namaToko' => config('warungku.nama_toko'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("laporan-{$tahun}-{$bulan}.pdf");
    }

    /**
     * Periode dari query string; default bulan & tahun berjalan.
     *
     * @return array{0:int, 1:int}
     */
    private function periode(Request $request): array
    {
        $bulan = (int) $request->input('bulan', date('n'));
        $tahun = (int) $request->input('tahun', date('Y'));

        // Jaga-jaga terhadap input di luar rentang (mis. ?bulan=99).
        $bulan = ($bulan >= 1 && $bulan <= 12) ? $bulan : (int) date('n');
        $tahun = ($tahun >= 2000 && $tahun <= 2999) ? $tahun : (int) date('Y');

        return [$bulan, $tahun];
    }

    /**
     * @return array<int, string>
     */
    private function namaBulan(): array
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }
}
