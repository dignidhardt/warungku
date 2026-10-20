@extends('layouts.app')

@section('judul', 'Detail Pembelian')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Pembelian {{ $pembelian->nomor_pembelian }}</h2>
            <p>
                {{ $pembelian->tanggal->translatedFormat('d F Y') }}
                @if ($pembelian->supplier) — {{ $pembelian->supplier }} @endif
            </p>
        </div>

        <div class="flex">
            <a href="{{ route('pembelian.index') }}" class="tombol abu">Kembali</a>
        </div>
    </div>

    <div class="kartu">
        <h3>Rincian Barang</h3>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Supplier</th>
                        <th class="angka">Qty</th>
                        <th class="angka">Harga Beli</th>
                        <th class="angka">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pembelian->items as $item)
                        <tr>
                            <td>{{ $item->produk->nama_produk ?? $item->stok_id }}</td>
                            <td class="kecil">{{ $item->supplier?->nama ?? '—' }}</td>
                            <td class="angka">{{ $item->qty }}</td>
                            <td class="angka">{{ uang($item->harga_beli) }}</td>
                            <td class="angka">{{ uang($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="kanan"><strong>Total</strong></td>
                        <td class="angka"><strong>{{ uang($pembelian->total) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($pembelian->keterangan)
            <p class="kecil" style="margin-top: 10px;">Keterangan: {{ $pembelian->keterangan }}</p>
        @endif

        <p class="kecil" style="margin-top: 6px;">
            Dicatat oleh {{ $pembelian->user->name ?? '—' }}
            pada {{ $pembelian->created_at->format('d/m/Y H:i') }}.
        </p>
    </div>

    <div class="kartu tanpa-cetak">
        <form action="{{ route('pembelian.batal', $pembelian) }}" method="POST"
              onsubmit="return confirm('Batalkan pembelian {{ $pembelian->nomor_pembelian }}? Stok akan ditarik kembali.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="tombol merah">Batalkan Pembelian</button>
        </form>
        <p class="kecil" style="margin-top: 8px;">
            Pembatalan hanya bisa dilakukan kalau stok barang itu belum terpakai. Kalau sudah terjual,
            sistem akan menolak agar stok tidak menjadi minus.
        </p>
    </div>
@endsection
