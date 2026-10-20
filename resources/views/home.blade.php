@extends('layouts.app')

@section('judul', 'Beranda')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Selamat datang, {{ auth()->user()->name }}</h2>
            <p>{{ now()->translatedFormat('l, d F Y') }} — ringkasan usaha warungku.</p>
        </div>
        <a href="{{ route('transaksi.index') }}" class="tombol hijau">Mulai Transaksi</a>
    </div>

    <div class="baris-statistik">
        <div class="statistik hijau">
            <div class="label">Omzet Hari Ini</div>
            <div class="nilai">{{ uang($totalPendapatanHariIni) }}</div>
        </div>

        <div class="statistik">
            <div class="label">Transaksi Hari Ini</div>
            <div class="nilai">{{ $transaksiHariIni }} <span class="satuan">nota</span></div>
        </div>

        <div class="statistik kuning">
            <div class="label">Laba Kotor Hari Ini</div>
            <div class="nilai">{{ uang($labaHariIni) }}</div>
        </div>

        <div class="statistik">
            <div class="label">Jumlah Produk</div>
            <div class="nilai">{{ $totalProduk }} <span class="satuan">item</span></div>
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        {{-- Ringkasan keuangan bulanan: ini yang dilihat pemilik untuk tahu
             kondisi usaha, bukan sekadar omzet harian. --}}
        <div class="baris-statistik">
            <div class="statistik hijau">
                <div class="label">Omzet Bersih Bulan Ini</div>
                <div class="nilai">{{ uang($labaRugi['omzet_bersih']) }}</div>
                <div class="kecil">{{ $totalTransaksiBulanIni }} nota</div>
            </div>

            <div class="statistik kuning">
                <div class="label">Laba Kotor Bulan Ini</div>
                <div class="nilai">{{ uang($labaRugi['laba_kotor']) }}</div>
                <div class="kecil">HPP {{ uang($labaRugi['hpp']) }}</div>
            </div>

            <div class="statistik merah">
                <div class="label">Biaya Operasional</div>
                <div class="nilai">{{ uang($labaRugi['biaya_operasional']) }}</div>
                <div class="kecil">Prive {{ uang($labaRugi['prive']) }} (bukan biaya)</div>
            </div>

            <div class="statistik {{ $labaRugi['laba_bersih'] >= 0 ? 'hijau' : 'merah' }}">
                <div class="label">Laba Bersih Bulan Ini</div>
                <div class="nilai">{{ uang($labaRugi['laba_bersih']) }}</div>
                <div class="kecil">Setelah biaya operasional</div>
            </div>
        </div>

        <div class="baris-statistik">
            <div class="kartu mb-0" style="min-width: 300px;">
                <h3>Nilai Persediaan</h3>
                <div class="statistik hijau" style="margin:0;">
                    <div class="label">Uang tertahan di rak</div>
                    <div class="nilai">{{ uang($nilaiPersediaan) }}</div>
                    <div class="kecil">Dihitung dari stok × harga beli</div>
                </div>

                <div class="baris-data" style="margin-top:12px;">
                    <div>Kas yang seharusnya ada hari ini</div>
                    <div class="angka">{{ uang($kasSeharusnyaHariIni) }}</div>
                </div>

                @if ($tutupBukuTerakhir)
                    <div class="baris-data">
                        <div>Tutup buku terakhir</div>
                        <div class="angka {{ 'selisih-'.$tutupBukuTerakhir->status_selisih }}">
                            {{ $tutupBukuTerakhir->tanggal->translatedFormat('d M Y') }}
                            ({{ $tutupBukuTerakhir->status_selisih }})
                        </div>
                    </div>
                @endif

                <a href="{{ route('keuangan.index') }}" class="tombol kecil abu" style="margin-top:12px;">
                    Lihat ringkasan keuangan
                </a>
            </div>

            <div class="kartu mb-0" style="min-width: 320px;">
                <h3>Saran Pembelian</h3>
                <p class="kecil">Barang yang stoknya menipis, diurutkan dari yang paling sering terjual.</p>

                @forelse ($saranPembelian as $saran)
                    <div class="baris-data">
                        <div>
                            <strong>{{ $saran['produk']->nama_produk }}</strong>
                            <div class="kecil">
                                sisa {{ $saran['produk']->labelStok($saran['produk']->stok) }}
                                @if ($saran['terjual'] > 0)
                                    · terjual {{ $saran['terjual'] }} bulan ini
                                @endif
                            </div>
                        </div>
                        <div class="angka">{{ $saran['saran'] }} {{ $saran['produk']->satuan }}</div>
                    </div>
                @empty
                    <p class="redup">Semua stok aman, tidak ada yang perlu dibeli sekarang.</p>
                @endforelse

                <a href="{{ route('pembelian.create') }}" class="tombol kecil abu" style="margin-top:12px;">
                    Catat pembelian
                </a>
            </div>
        </div>
    @endif

    <div class="baris-statistik">
        <div class="kartu mb-0" style="grid-column: span 2; min-width: 320px;">
            <h3>Perlu Perhatian</h3>

            @if ($stokHabis->isNotEmpty())
                <div class="pesan galat" style="margin-bottom:12px;">
                    {{ $stokHabis->count() }} produk stoknya habis:
                    {{ $stokHabis->take(3)->pluck('nama_produk')->join(', ') }}@if ($stokHabis->count() > 3) dan lainnya @endif.
                </div>
            @endif

            @forelse ($stokMenipis as $item)
                <div class="flex" style="justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--garis);">
                    <div>
                        <strong>{{ $item->nama_produk }}</strong>
                        <div class="kecil redup">{{ $item->barcode }} · {{ $item->kategori }}</div>
                    </div>
                    <span class="lencana {{ $item->stok <= 0 ? 'merah' : 'kuning' }}">
                        {{ $item->stok <= 0 ? 'Habis' : $item->labelStok($item->stok) }}
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
                    <strong>{{ $item['qty'] }}</strong>
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
                            <th>Bayar</th>
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
                                <td class="kecil">{{ $trx->label_metode }}</td>
                                <td class="angka">{{ uang($trx->grand_total) }}</td>
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
