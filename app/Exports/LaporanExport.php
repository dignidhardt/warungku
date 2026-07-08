<?php

namespace App\Exports;

use App\Models\Transaksi;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LaporanExport implements FromView
{
    protected $bulan;
    protected $tahun;

    public function __construct($bulan, $tahun)
    {
        $this->bulan = (int) $bulan;
        $this->tahun = (int) $tahun;
    }

    public function view(): View
    {
        $query = Transaksi::whereMonth('created_at', $this->bulan)
            ->whereYear('created_at', $this->tahun);

        return view('ekports', [
            'laporan' => $query->get(),
            'total'   => $query->sum('grand_total')
        ]);
    }
}