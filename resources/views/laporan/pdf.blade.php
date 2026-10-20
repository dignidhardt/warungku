<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan {{ $bulan }} {{ $tahun }} — {{ $namaToko }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10.5px;
            color: #111;
            padding: 18px;
        }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .sub { color: #555; font-size: 10px; margin-bottom: 14px; }
        .ringkasan { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .ringkasan td {
            border: 1px solid #ccc;
            padding: 7px 9px;
            width: 25%;
        }
        .ringkasan .label {
            font-size: 8.5px;
            text-transform: uppercase;
            color: #555;
            letter-spacing: .04em;
        }
        .ringkasan .nilai { font-size: 13px; font-weight: bold; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td {
            border: 1px solid #ccc;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }
        table.data th {
            background: #eef2f7;
            font-size: 9.5px;
            text-transform: uppercase;
        }
        table.data tfoot td { font-weight: bold; background: #f5f7fa; }
        .angka { text-align: right; }
        .tengah { text-align: center; }
        h2 { font-size: 12px; margin: 16px 0 6px; }
    </style>
</head>
<body>

<h1>{{ $namaToko }}</h1>
<div class="sub">Laporan Penjualan — {{ $bulan }} {{ $tahun }}</div>

<table class="ringkasan">
    <tr>
        <td>
            <div class="label">Omzet</div>
            <div class="nilai">{{ uang($ringkasan['omzet']) }}</div>
        </td>
        <td>
            <div class="label">Laba Kotor</div>
            <div class="nilai">{{ uang($ringkasan['laba']) }}</div>
        </td>
        <td>
            <div class="label">Jumlah Nota</div>
            <div class="nilai">{{ $ringkasan['jumlah_transaksi'] }}</div>
        </td>
        <td>
            <div class="label">Item Terjual</div>
            <div class="nilai">{{ $ringkasan['jumlah_item'] }} pcs</div>
        </td>
    </tr>
</table>

<h2>Rincian Transaksi</h2>

<table class="data">
    <thead>
        <tr>
            <th style="width:6%;">No</th>
            <th style="width:24%;">Nomor Nota</th>
            <th style="width:16%;">Tanggal</th>
            <th style="width:16%;">Kasir</th>
            <th>Item</th>
            <th style="width:15%;" class="angka">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($transaksi as $i => $trx)
            <tr>
                <td class="tengah">{{ $i + 1 }}</td>
                <td>{{ $trx->nomor_transaksi }}</td>
                <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $trx->kasir->name ?? '—' }}</td>
                <td>
                    @foreach ($trx->items as $item)
                        {{ $item['nama'] ?? 'Produk' }} (x{{ $item['qty'] ?? 0 }}){{ ! $loop->last ? ', ' : '' }}
                    @endforeach
                </td>
                <td class="angka">{{ uang($trx->grand_total) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="tengah">Belum ada transaksi pada periode ini.</td>
            </tr>
        @endforelse
    </tbody>
    @if ($transaksi->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="5" class="angka">Total Omzet</td>
                <td class="angka">{{ uang($ringkasan['omzet']) }}</td>
            </tr>
        </tfoot>
    @endif
</table>

@if ($produkTerlaris->isNotEmpty())
    <h2>Produk Terlaris</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width:8%;">#</th>
                <th>Produk</th>
                <th style="width:20%;" class="tengah">Terjual</th>
                <th style="width:22%;" class="angka">Omzet</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($produkTerlaris as $i => $item)
                <tr>
                    <td class="tengah">{{ $i + 1 }}</td>
                    <td>{{ $item['nama'] }}</td>
                    <td class="tengah">{{ $item['qty'] }} pcs</td>
                    <td class="angka">{{ uang($item['omzet']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div style="margin-top:14px; font-size:9px; color:#666;">
    Dicetak {{ now()->format('d/m/Y H:i') }} · Transaksi dibatalkan tidak dihitung.
</div>

</body>
</html>
