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

    <div class="card" style="background:#fff;padding:25px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:20px;">

        <form action="{{ route('laporan.index') }}" method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;">

            <div style="flex:1;min-width:150px;">
                <label style="display:block;margin-bottom:8px;font-weight:bold;">
                    Pilih Periode Bulan
                </label>

                <select name="bulan"
                    style="width:100%;height:42px;padding:0 10px;border:1px solid #ced4da;border-radius:6px;">

                    @php
                        $namaBulan = [
                            '01'=>'Januari',
                            '02'=>'Februari',
                            '03'=>'Maret',
                            '04'=>'April',
                            '05'=>'Mei',
                            '06'=>'Juni',
                            '07'=>'Juli',
                            '08'=>'Agustus',
                            '09'=>'September',
                            '10'=>'Oktober',
                            '11'=>'November',
                            '12'=>'Desember'
                        ];
                    @endphp

                    @foreach($namaBulan as $ang=>$nama)
                        <option value="{{ $ang }}" {{ $bulanSelected==$ang?'selected':'' }}>
                            {{ $nama }}
                        </option>
                    @endforeach

                </select>
            </div>

            <div style="flex:1;min-width:120px;">
                <label style="display:block;margin-bottom:8px;font-weight:bold;">
                    Pilih Tahun
                </label>

                <select name="tahun"
                    style="width:100%;height:42px;padding:0 10px;border:1px solid #ced4da;border-radius:6px;">

                    @foreach($daftarTahun as $thn)
                        <option value="{{ $thn }}" {{ $tahunSelected==$thn?'selected':'' }}>
                            {{ $thn }}
                        </option>
                    @endforeach

                </select>
            </div>

            <div style="display:flex;gap:10px;">

                <a href="{{ route('laporan.excel',['bulan'=>$bulanSelected,'tahun'=>$tahunSelected]) }}"
                    style="display:flex;align-items:center;justify-content:center;background:#198754;color:white;height:42px;padding:0 20px;border-radius:6px;text-decoration:none;font-weight:bold;">
                    Export ke Excel
                </a>

                <button type="submit"
                    style="background:#0d6efd;color:white;height:42px;padding:0 20px;border:none;border-radius:6px;font-weight:bold;cursor:pointer;">
                    Filter Laporan
                </button>

            </div>

        </form>

    </div>

    <div class="card"
        style="background:#198754;color:white;padding:20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:25px;">

        <span style="font-size:14px;color:#d1e7dd;">
            TOTAL PENDAPATAN HARI INI
        </span>

        <h2 style="margin-top:10px;">
            Rp {{ number_format($totalHariIni,0,',','.') }}
        </h2>

    </div>

    <div class="card"
        style="background:#fff;padding:25px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.05);">

        <h5 style="margin-bottom:20px;">
            Riwayat Transaksi Penjualan
        </h5>

        <div style="overflow-x:auto;">

            <table style="width:100%;border-collapse:collapse;">

                <thead>

                    <tr style="background:#f8fafc;border-bottom:2px solid #dee2e6;">

                        <th style="padding:12px;text-align:center;">No</th>
                        <th style="padding:12px;">No. Nota</th>
                        <th style="padding:12px;">Tanggal & Jam</th>
                        <th style="padding:12px;">Item Dibeli</th>
                        <th style="padding:12px;text-align:right;">Total</th>

                    </tr>

                </thead>

                <tbody>

                @forelse($laporan as $row)

                    <tr style="border-bottom:1px solid #eee;">

                        <td style="padding:12px;text-align:center;">
                            {{ $loop->iteration }}
                        </td>

                        <td style="padding:12px;font-weight:bold;color:#0d6efd;">
                            {{ $row->nomor_transaksi }}
                        </td>

                        <td style="padding:12px;">
                            {{ $row->created_at->format('d M Y H:i') }}
                        </td>

                        <td style="padding:12px;">

                            <ul style="margin:0;padding-left:18px;">

                                @foreach(json_decode($row->detail_produk,true) as $item)

                                    <li>
                                        {{ $item['nama'] }} (x{{ $item['qty'] }})
                                    </li>

                                @endforeach

                            </ul>

                        </td>

                        <td style="padding:12px;text-align:right;font-weight:bold;color:#198754;">
                            Rp {{ number_format($row->grand_total,0,',','.') }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5" style="padding:30px;text-align:center;color:#888;">
                            Belum ada transaksi.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection