@extends('layouts.app')

@section('judul', 'Beranda')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Selamat datang, {{ auth()->user()->name }}</h2>
            <p>Ringkasan penjualan hari ini — {{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        <a href="{{ route('transaksi.index') }}" class="tombol hijau">Mulai Transaksi</a>
    </div>

    <div class="baris-statistik">
        <div class="statistik hijau">
            <div class="label">Omzet Hari Ini</div>
            <div class="nilai">Rp {{ number_format($totalPendapatanHariIni, 0, ',', '.') }}</div>
        </div>

        <div class="statistik">
            <div class="label">Transaksi Hari Ini</div>
            <div class="nilai">{{ $transaksiHariIni }} <span class="satuan">nota</span></div>
        </div>

        <div class="statistik kuning">
            <div class="label">Laba Kotor Hari Ini</div>
            <div class="nilai">Rp {{ number_format($labaHariIni, 0, ',', '.') }}</div>
        </div>

        <div class="statistik">
            <div class="label">Jumlah Produk</div>
            <div class="nilai">{{ $totalProduk }} <span class="satuan">item</span></div>
        </div>
    </div>

    <div class="baris-statistik">
        <div class="kartu mb-0" style="grid-column: span 2; min-width: 320px;">
            <h3>Perlu Perhatian</h3>

            @if ($stokHabis > 0)
                <div class="pesan galat" style="margin-bottom:12px;">
                    {{ $stokHabis }} produk stoknya habis.
                </div>
            @endif

            @forelse ($stokMenipis as $item)
                <div class="flex" style="justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--garis);">
                    <div>
                        <strong>{{ $item->nama_produk }}</strong>
                        <div class="kecil redup">{{ $item->barcode }} · {{ $item->kategori }}</div>
                    </div>
                    <span class="lencana {{ $item->stok <= 0 ? 'merah' : 'kuning' }}">
                        {{ $item->stok <= 0 ? 'Habis' : $item->stok.' tersisa' }}
                    </span>
                </div>
            @empty
                <p class="redup">Semua stok dalam kondisi aman.</p>
            @endforelse

            <a href="{{ route('stok.index', ['status' => 'menipis']) }}" class="tombol kecil abu" style="margin-top:14px;">
                Lihat semua stok menipis
            </a>
        </div>

        <div class="kartu mb-0" style="min-width: 260px;">
            <h3>Terlaris Hari Ini</h3>

            @forelse ($produkTerlaris as $i => $item)
                <div class="flex" style="justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--garis);">
                    <span>{{ $i + 1 }}. {{ $item['nama'] }}</span>
                    <strong>{{ $item['qty'] }} pcs</strong>
                </div>
            @empty
                <p class="redup">Belum ada penjualan hari ini.</p>
            @endforelse
        </div>
    </div>

    <div class="kartu">
        <h3>Transaksi Terakhir</h3>

        @if ($transaksiTerakhir->isEmpty())
            <p class="kosong">Belum ada transaksi hari ini.</p>
        @else
            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>Nomor Nota</th>
                            <th>Jam</th>
                            <th>Kasir</th>
                            <th class="angka">Total</th>
                            <th class="tengah">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transaksiTerakhir as $trx)
                            <tr>
                                <td><strong>{{ $trx->nomor_transaksi }}</strong></td>
                                <td>{{ $trx->created_at->format('H:i') }}</td>
                                <td>{{ $trx->kasir->name ?? '—' }}</td>
                                <td class="angka">Rp {{ number_format($trx->grand_total, 0, ',', '.') }}</td>
                                <td class="tengah">
                                    <a href="{{ route('transaksi.struk', $trx) }}" class="tombol kecil abu">Struk</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <a href="{{ route('transaksi.riwayat') }}" class="tombol kecil abu" style="margin-top:14px;">
                Lihat riwayat lengkap
            </a>
        @endif
    </div>
@endsection
