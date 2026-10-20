@extends('layouts.app')

@section('judul', 'Pembelian')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Pembelian Barang</h2>
            <p>Catat pembelian ke supplier. Stok bertambah dan harga beli produk ikut diperbarui.</p>
        </div>

        <div class="flex">
            <a href="{{ route('pembelian.create') }}" class="tombol">+ Pembelian Baru</a>
        </div>
    </div>

    <div class="kartu">
        <form action="{{ route('pembelian.index') }}" method="GET" class="baris-filter">
            <div class="bidang cari">
                <label for="cari">Cari</label>
                <input type="text" id="cari" name="cari" value="{{ $cari }}" placeholder="Nomor pembelian atau supplier">
            </div>
            <button type="submit" class="tombol">Cari</button>
        </form>
    </div>

    <div class="kartu">
        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>No. Pembelian</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th>Oleh</th>
                        <th class="angka">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pembelians as $item)
                        <tr>
                            <td>
                                <a href="{{ route('pembelian.show', $item) }}">{{ $item->nomor_pembelian }}</a>
                            </td>
                            <td>{{ $item->tanggal->translatedFormat('d M Y') }}</td>
                            <td>{{ $item->supplier ?: '—' }}</td>
                            <td class="kecil">{{ $item->user->name ?? '—' }}</td>
                            <td class="angka">{{ uang($item->total) }}</td>
                            <td class="kanan">
                                <a href="{{ route('pembelian.show', $item) }}" class="tombol abu kecil">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="kosong">Belum ada pembelian tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $pembelians->links() }}
    </div>
@endsection
