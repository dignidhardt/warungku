<?php

namespace App\Exports;

use App\Services\LaporanService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LaporanExport implements FromView, ShouldAutoSize
{
    public function __construct(
        private readonly int $bulan,
        private readonly int $tahun
    ) {}

    public function view(): View
    {
        // Dihitung lewat service yang sama dengan halaman laporan, supaya
        // angka di Excel tidak mungkin berbeda dari yang tampil di layar.
        $laporan = app(LaporanService::class);
        $transaksi = $laporan->transaksiPeriode($this->bulan, $this->tahun);

        return view('laporan.excel', [
            'transaksi' => $transaksi,
            'ringkasan' => $laporan->ringkasan($transaksi),
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'namaToko' => config('warungku.nama_toko'),
        ]);
    }
}
