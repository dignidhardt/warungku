@extends('layouts.app')

@section('judul', 'Laporan')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Laporan Penjualan</h2>
            <p>Ringkasan omzet, laba kotor, dan produk terlaris.</p>
        </div>

        <div class="flex">
            <a href="{{ route('laporan.excel', ['bulan' => $bulanSelected, 'tahun' => $tahunSelected]) }}" class="tombol hijau">Export Excel</a>
            <a href="{{ route('laporan.pdf', ['bulan' => $bulanSelected, 'tahun' => $tahunSelected]) }}" class="tombol merah">Export PDF</a>
        </div>
    </div>

    <div class="kartu">
        <form action="{{ route('laporan.index') }}" method="GET" class="baris-filter">
            <div class="bidang">
                <label for="bulan">Bulan</label>
                <select id="bulan" name="bulan">
                    @foreach ($namaBulan as $angka => $nama)
                        <option value="{{ $angka }}" @selected((int) $bulanSelected === $angka)>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bidang">
                <label for="tahun">Tahun</label>
                <select id="tahun" name="tahun">
                    @foreach ($daftarTahun as $tahun)
                        <option value="{{ $tahun }}" @selected((int) $tahunSelected === (int) $tahun)>{{ $tahun }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="tombol">Tampilkan</button>
        </form>
    </div>

    <div class="baris-statistik">
        <div class="statistik hijau">
            <div class="label">Omzet {{ $namaBulan[(int) $bulanSelected] }} {{ $tahunSelected }}</div>
            <div class="nilai">Rp {{ number_format($ringkasan['omzet'], 0, ',', '.') }}</div>
        </div>
        <div class="statistik kuning">
            <div class="label">Laba Kotor</div>
            <div class="nilai">Rp {{ number_format($ringkasan['laba'], 0, ',', '.') }}</div>
        </div>
        <div class="statistik">
            <div class="label">Jumlah Nota</div>
            <div class="nilai">{{ $ringkasan['jumlah_transaksi'] }} <span class="satuan">nota</span></div>
        </div>
        <div class="statistik">
            <div class="label">Item Terjual</div>
            <div class="nilai">{{ $ringkasan['jumlah_item'] }} <span class="satuan">pcs</span></div>
        </div>
    </div>

    <div class="pesan info">
        Omzet hari ini ({{ now()->translatedFormat('d F Y') }}):
        <strong>Rp {{ number_format($hariIni['omzet'], 0, ',', '.') }}</strong>
        dari {{ $hariIni['jumlah_transaksi'] }} nota.
        Transaksi yang dibatalkan tidak dihitung.
    </div>

    <div class="baris-statistik">
        <div class="kartu mb-0" style="min-width: 320px;">
            <h3>Produk Terlaris</h3>

            @if ($produkTerlaris->isEmpty())
                <p class="redup">Belum ada penjualan pada periode ini.</p>
            @else
                <div class="tabel-bungkus">
                    <table class="tabel">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th class="tengah">Terjual</th>
                                <th class="angka">Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($produkTerlaris as $i => $item)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $item['nama'] }}</td>
                                    <td class="tengah"><strong>{{ $item['qty'] }}</strong> pcs</td>
                                    <td class="angka">Rp {{ number_format($item['omzet'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="kartu">
        <h3>Rincian Transaksi</h3>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>No. Nota</th>
                        <th>Tanggal</th>
                        <th>Kasir</th>
                        <th>Item</th>
                        <th class="angka">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transaksi as $trx)
                        <tr>
                            <td>
                                <a href="{{ route('transaksi.struk', $trx) }}">{{ $trx->nomor_transaksi }}</a>
                            </td>
                            <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $trx->kasir->name ?? '—' }}</td>
                            <td class="kecil">
                                @foreach ($trx->items as $item)
                                    {{ $item['nama'] ?? 'Produk' }} (x{{ $item['qty'] ?? 0 }}){{ ! $loop->last ? ', ' : '' }}
                                @endforeach
                            </td>
                            <td class="angka">Rp {{ number_format($trx->grand_total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="kosong">Belum ada transaksi pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($transaksi->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="4" class="kanan">Total</td>
                            <td class="angka">Rp {{ number_format($ringkasan['omzet'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
