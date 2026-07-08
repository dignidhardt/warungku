<table>
    <thead>
        <tr>
            <th>No</th><th>No. Nota</th><th>Tanggal</th><th>Item</th><th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($laporan as $index => $row)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $row->nomor_transaksi }}</td>
            <td>{{ $row->created_at }}</td>
<td>
    @php
        $detail = is_string($row->detail_produk)
            ? json_decode($row->detail_produk, true)
            : $row->detail_produk;
    @endphp

    @if(is_array($detail))
        @foreach($detail as $produk)
            {{ $produk['nama'] ?? '-' }}
            @if(isset($produk['qty']))
                ({{ $produk['qty'] }}x)
            @endif
            <br>
        @endforeach
    @endif
</td>            <td>{{ $row->grand_total }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">TOTAL KESELURUHAN</td>
            <td>{{ $total }}</td>
        </tr>
    </tfoot>
</table>