<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $transaksi->nomor_transaksi }} — {{ config('warungku.nama_toko') }}</title>
    <link rel="stylesheet" href="{{ asset('css/warungku.css') }}">
    <style>
        body {
            background: #e5e7eb;
            padding: 24px 16px;
            font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
        }
        .struk {
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
            background: #fff;
            padding: 22px 18px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .12);
            font-size: 12.5px;
            line-height: 1.55;
            color: #111;
        }
        .struk .toko {
            text-align: center;
            margin-bottom: 12px;
        }
        .struk .toko strong {
            display: block;
            font-size: 15px;
            letter-spacing: .04em;
        }
        .struk .toko span { font-size: 11px; color: #555; }
        .struk hr {
            border: 0;
            border-top: 1px dashed #999;
            margin: 10px 0;
        }
        .struk table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .struk td { padding: 2px 0; vertical-align: top; }
        .struk td.kanan { text-align: right; }
        .struk .nama-item { display: block; }
        .struk .baris-total td {
            font-weight: 700;
            padding-top: 6px;
        }
        .struk .kembali td {
            font-weight: 700;
            font-size: 13.5px;
        }
        .struk .catatan {
            text-align: center;
            font-size: 11px;
            color: #555;
            margin-top: 12px;
        }
        .struk .batal {
            border: 2px solid #dc3545;
            color: #dc3545;
            text-align: center;
            font-weight: 700;
            padding: 6px;
            margin: 10px 0;
            letter-spacing: .1em;
        }
        .aksi-cetak {
            max-width: 320px;
            margin: 18px auto 0;
            display: flex;
            gap: 8px;
        }
        .aksi-cetak a, .aksi-cetak button {
            flex: 1;
            text-align: center;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .struk { box-shadow: none; max-width: 100%; padding: 0; }
            .aksi-cetak, .tanpa-cetak { display: none !important; }
        }
    </style>
</head>
<body>

<div class="struk">
    <div class="toko">
        <strong>{{ config('warungku.nama_toko') }}</strong>
        @if (config('warungku.alamat_toko'))
            <span>{{ config('warungku.alamat_toko') }}</span><br>
        @endif
        @if (config('warungku.telepon_toko'))
            <span>Telp. {{ config('warungku.telepon_toko') }}</span>
        @endif
    </div>

    <hr>

    <table>
        <tr>
            <td>No. Nota</td>
            <td class="kanan">{{ $transaksi->nomor_transaksi }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td class="kanan">{{ $transaksi->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td class="kanan">{{ $transaksi->kasir->name ?? '—' }}</td>
        </tr>
    </table>

    @if ($transaksi->sudah_dibatalkan)
        <div class="batal">DIBATALKAN</div>
    @endif

    <hr>

    <table>
        @foreach ($transaksi->items as $item)
            <tr>
                <td colspan="2">
                    <span class="nama-item">{{ $item['nama'] ?? 'Produk' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    {{ (int) ($item['qty'] ?? 0) }} {{ $item['satuan'] ?? 'pcs' }} x
                    {{ uang((int) ($item['harga'] ?? 0)) }}
                </td>
                <td class="kanan">
                    {{ uang((int) ($item['subtotal'] ?? (($item['harga'] ?? 0) * ($item['qty'] ?? 0)))) }}
                </td>
            </tr>
        @endforeach
    </table>

    <hr>

    <table>
        <tr class="baris-total">
            <td>TOTAL</td>
            <td class="kanan">{{ uang($transaksi->grand_total) }}</td>
        </tr>
        @if ($transaksi->diskon > 0)
            <tr>
                <td>Diskon</td>
                <td class="kanan">{{ uang($transaksi->diskon) }}</td>
            </tr>
        @endif
        <tr>
            <td>Bayar ({{ $transaksi->label_metode }})</td>
            <td class="kanan">{{ uang($transaksi->bayar) }}</td>
        </tr>
        @if ($transaksi->metode_bayar === 'tunai')
            <tr class="kembali">
                <td>Kembali</td>
                <td class="kanan">{{ uang($transaksi->kembali) }}</td>
            </tr>
        @endif
    </table>

    <hr>

    <div class="catatan">
        Terima kasih telah berbelanja<br>
        Barang yang sudah dibeli tidak dapat ditukar
    </div>
</div>

<div class="aksi-cetak tanpa-cetak">
    <button type="button" class="tombol hijau" onclick="window.print()">Cetak</button>
    <a href="{{ route('transaksi.index') }}" class="tombol abu">Transaksi Baru</a>
</div>

</body>
</html>
