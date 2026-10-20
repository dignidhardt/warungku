@extends('layouts.app')

@section('judul', 'Retur Transaksi')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Retur {{ $transaksi->nomor_transaksi }}</h2>
            <p>
                {{ $transaksi->created_at->translatedFormat('d F Y H:i') }}
                — kasir {{ $transaksi->kasir->name ?? '—' }}
            </p>
        </div>

        <div class="flex">
            <a href="{{ route('transaksi.riwayat') }}" class="tombol abu">Kembali</a>
        </div>
    </div>

    @if ($items === [])
        <div class="pesan info">
            Semua barang pada nota ini sudah diretur. Tidak ada yang bisa diretur lagi.
        </div>
    @else
        <form action="{{ route('transaksi.retur.simpan', $transaksi) }}" method="POST">
            @csrf

            <div class="kartu">
                <h3>Barang yang Dikembalikan</h3>
                <p class="kecil">
                    Isi jumlah yang dikembalikan. Barang yang dikembalikan otomatis masuk lagi ke stok.
                </p>

                @foreach ($items as $item)
                    <div class="baris-barang">
                        <div class="nama-barang">
                            <strong>{{ $item['nama'] }}</strong>
                            <div class="kecil">
                                Dibeli {{ $item['qty'] }} — sisa bisa diretur {{ $item['sisa'] }}
                                @if ($item['sisa'] < $item['qty'])
                                    (sudah pernah diretur sebagian)
                                @endif
                            </div>
                        </div>
                        <div class="angka">{{ uang($item['harga']) }}</div>
                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item['id'] }}">
                        <input type="number" name="items[{{ $loop->index }}][qty]" min="0"
                               max="{{ $item['sisa'] }}" value="0" class="jumlah-retur">
                    </div>
                @endforeach

                <div class="bidang" style="margin-top: 14px;">
                    <label for="alasan">Alasan Retur</label>
                    <input type="text" id="alasan" name="alasan" value="{{ old('alasan') }}"
                           placeholder="mis. barang rusak, salah ambil" required>
                </div>

                <div class="flex" style="margin-top: 14px;">
                    <button type="submit" class="tombol">Simpan Retur</button>
                </div>
            </div>
        </form>
    @endif

    @if ($returs->isNotEmpty())
        <div class="kartu">
            <h3>Retur yang Pernah Tercatat</h3>

            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>No. Retur</th>
                            <th>Waktu</th>
                            <th>Alasan</th>
                            <th class="angka">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($returs as $retur)
                            <tr>
                                <td>{{ $retur->nomor_retur }}</td>
                                <td>{{ $retur->tanggal->format('d/m/Y H:i') }}</td>
                                <td class="kecil">{{ $retur->alasan }}</td>
                                <td class="angka">{{ uang($retur->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="pesan info">
        <strong>Retur berbeda dengan pembatalan.</strong> Retur hanya mengoreksi sebagian barang —
        nota penjualan tetap ada karena penjualan itu memang terjadi. Pembatalan membatalkan seluruh
        nota dan dipakai kalau transaksi memang salah total.
    </div>
@endsection
