@extends('layouts.app')

@section('content')
<style>
    .card, 
    .card-body,
    div[class*="container"], 
    div[class*="col"] {
        max-width: 100% !important;
        width: 100% !important;
    }
</style>

<div style="width: 100%; padding-right: 30px; box-sizing: border-box; margin-bottom: 30px;">
    
    <div style="margin-bottom: 20px;">
    </div>

    <div class="card" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); margin-bottom: 20px; box-sizing: border-box;">
        <form action="{{ route('laporan.index') }}" method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; font-family: sans-serif; font-size: 14px; color: #555;">
                    Pilih Periode Bulan
                </label>
                <select name="bulan" onchange="this.form.submit()" style="width: 100%; height: 42px; padding: 0 10px; border: 1px solid #ced4da; border-radius: 6px; font-family: sans-serif; font-size: 14px; color: #495057; background-color: #fff; cursor: pointer;">
                    @php
                        $namaBulan = [
                            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                        ];
                    @endphp
                    @foreach($namaBulan as $ang => $nama)
                        <option value="{{ $ang }}" {{ $bulanSelected == $ang ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

   <div style="flex: 1; min-width: 120px;">
    <label style="display: block; margin-bottom: 8px; font-weight: bold; font-family: sans-serif; font-size: 14px; color: #555;">
        Pilih Tahun
    </label>
    <select name="tahun" onchange="this.form.submit()" style="width: 100%; height: 42px; padding: 0 10px; border: 1px solid #ced4da; border-radius: 6px; font-family: sans-serif; font-size: 14px; color: #495057; background-color: #fff; cursor: pointer;">
        @foreach($daftarTahun as $thn)
            <option value="{{ $thn }}" {{ $tahunSelected == $thn ? 'selected' : '' }}>
                {{ $thn }}
            </option>
        @endforeach
    </select>
</div>

            <div>
                <a href="{{ route('laporan.excel', ['bulan' => $bulanSelected, 'tahun' => $tahunSelected]) }}" style="display: flex; align-items: center; justify-content: center; background: #198754; color: white; height: 42px; padding: 0 20px; border-radius: 6px; cursor: pointer; font-weight: bold; font-family: sans-serif; font-size: 14px; text-decoration: none; box-sizing: border-box;">
                    Export ke Excel
                </a>
                <button type="submit" style="background: #0d6efd; color: white; height: 40px; padding: 0 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-family: sans-serif; font-size: 14px;">
                    Filter Laporan
                </button>
            </div>

        </form>
    </div>

    <div class="card" style="background: #198754; color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); margin-bottom: 25px; width: 100%;">
        <span style="font-family: sans-serif; font-size: 14px; color: #d1e7dd; display: block; margin-bottom: 5px;">TOTAL PENDAPATAN BULAN INI</span>
        <strong style="font-family: sans-serif; font-size: 28px;">Rp {{ number_format($totalOmset, 0, ',', '.') }}</strong>
    </div>

    <div class="card" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); width: 100%; box-sizing: border-box;">
        <h5 style="margin-top: 0; margin-bottom: 20px; font-family: sans-serif; color: #444; border-bottom: 1px solid #eee; padding-bottom: 10px;">Riwayat Transaksi Penjualan</h5>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 14px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #dee2e6; text-align: left;">
                        <th style="padding: 12px 10px; text-align: center; width: 50px;">No</th>
                        <th style="padding: 12px 10px; width: 150px;">No. Nota</th>
                        <th style="padding: 12px 10px; width: 160px;">Tanggal & Jam</th>
                        <th style="padding: 12px 10px;">Item yang Dibeli (Qty)</th>
                        <th style="padding: 12px 10px; text-align: right; width: 140px;">Total Belanja</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($laporan as $row)
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px 10px; text-align: center;">{{ $loop->iteration }}</td>
                        <td style="padding: 12px 10px; font-weight: bold; color: #0d6efd;">{{ $row->nomor_transaksi }}</td>
                        <td style="padding: 12px 10px; color: #666;">{{ $row->created_at->format('d M Y - H:i') }} WIB</td>
                        <td style="padding: 12px 10px;">
                            <ul style="margin: 0; padding-left: 15px; color: #333;">
                                @foreach(json_decode($row->detail_produk, true) as $item)
                                    <li>{{ $item['nama'] }} <span style="color:#666;">(x{{ $item['qty'] }})</span></li>
                                @endforeach
                            </ul>
                        </td>
                        <td style="padding: 12px 10px; text-align: right; font-weight: bold; color: #198754;">
                            Rp {{ number_format($row->grand_total, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 30px; text-align: center; color: #888; font-style: italic;">
                            Tidak ada riwayat transaksi pada bulan & tahun yang dipilih.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection