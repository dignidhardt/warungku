<table>
    <thead>
        <tr>
            <th colspan="6" style="font-weight: bold; font-size: 14px; text-align: left;">
                {{ $namaToko }} — Laporan Penjualan {{ $bulan }}/{{ $tahun }}
            </th>
        </tr>
        <tr>
            <th style="background: #eef2f7; font-weight: bold;">No</th>
            <th style="background: #eef2f7; font-weight: bold;">Nomor Nota</th>
            <th style="background: #eef2f7; font-weight: bold;">Tanggal</th>
            <th style="background: #eef2f7; font-weight: bold;">Kasir</th>
            <th style="background: #eef2f7; font-weight: bold;">Item</th>
            <th style="background: #eef2f7; font-weight: bold;">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($transaksi as $i => $trx)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $trx->nomor_transaksi }}</td>
                <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $trx->kasir->name ?? '—' }}</td>
                <td>
                    @foreach ($trx->items as $item)
                        {{ $item['nama'] ?? 'Produk' }} (x{{ $item['qty'] ?? 0 }}){{ ! $loop->last ? ', ' : '' }}
                    @endforeach
                </td>
                <td>{{ $trx->grand_total }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">Belum ada transaksi pada periode ini.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="font-weight: bold;">Total Omzet</td>
            <td style="font-weight: bold;">{{ $ringkasan['omzet'] }}</td>
        </tr>
        <tr>
            <td colspan="5" style="font-weight: bold;">Laba Kotor</td>
            <td style="font-weight: bold;">{{ $ringkasan['laba'] }}</td>
        </tr>
        <tr>
            <td colspan="5" style="font-weight: bold;">Jumlah Nota</td>
            <td style="font-weight: bold;">{{ $ringkasan['jumlah_transaksi'] }}</td>
        </tr>
    </tfoot>
</table>
