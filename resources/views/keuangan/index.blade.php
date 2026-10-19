@extends('layouts.app')

@section('judul', 'Keuangan')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Ringkasan Keuangan</h2>
            <p>Omzet, laba, dan arus uang warung pada satu periode.</p>
        </div>

        <div class="flex">
            <a href="{{ route('keuangan.laba-rugi', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol">Laba Rugi</a>
            <a href="{{ route('keuangan.buku-kas', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol abu">Buku Kas</a>
        </div>
    </div>

    <div class="kartu">
        <form action="{{ route('keuangan.index') }}" method="GET" class="baris-filter">
            <div class="bidang">
                <label for="bulan">Bulan</label>
                <select id="bulan" name="bulan">
                    @foreach (range(1, 12) as $b)
                        <option value="{{ $b }}" @selected($b === $bulan)>
                            {{ \Illuminate\Support\Carbon::create(null, $b, 1)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="bidang">
                <label for="tahun">Tahun</label>
                <select id="tahun" name="tahun">
                    @foreach ($daftarTahun as $t)
                        <option value="{{ $t }}" @selected($t === $tahun)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="tombol">Tampilkan</button>
        </form>
    </div>

    <div class="baris-statistik">
        <div class="statistik hijau">
            <div class="label">Omzet Bersih</div>
            <div class="nilai">Rp {{ number_format($labaRugi['omzet_bersih'], 0, ',', '.') }}</div>
            <div class="kecil">
                Penjualan Rp {{ number_format($labaRugi['penjualan'], 0, ',', '.') }}
                @if ($labaRugi['retur'] > 0)
                    − retur Rp {{ number_format($labaRugi['retur'], 0, ',', '.') }}
                @endif
            </div>
        </div>

        <div class="statistik kuning">
            <div class="label">Laba Kotor</div>
            <div class="nilai">Rp {{ number_format($labaRugi['laba_kotor'], 0, ',', '.') }}</div>
            <div class="kecil">Setelah harga pokok Rp {{ number_format($labaRugi['hpp'], 0, ',', '.') }}</div>
        </div>

        <div class="statistik">
            <div class="label">Biaya Operasional</div>
            <div class="nilai">Rp {{ number_format($labaRugi['biaya_operasional'], 0, ',', '.') }}</div>
            <div class="kecil">Tidak termasuk prive</div>
        </div>

        <div class="statistik {{ $labaRugi['laba_bersih'] >= 0 ? 'hijau' : 'merah' }}">
            <div class="label">Laba Bersih</div>
            <div class="nilai">Rp {{ number_format($labaRugi['laba_bersih'], 0, ',', '.') }}</div>
            <div class="kecil">{{ $labaRugi['jumlah_transaksi'] }} nota</div>
        </div>
    </div>

    @if ($labaRugi['prive'] > 0)
        <div class="pesan info">
            <strong>Prive bulan ini: Rp {{ number_format($labaRugi['prive'], 0, ',', '.') }}</strong> —
            uang yang diambil pemilik untuk keperluan pribadi. Tidak dihitung biaya usaha, jadi tidak
            mengurangi laba di atas, tapi tetap mengurangi uang di laci.
            <a href="{{ route('keuangan.buku-kas', ['bulan' => $bulan, 'tahun' => $tahun]) }}">Lihat di Buku Kas</a>.
        </div>
    @endif

    <div class="baris-statistik">
        <div class="kartu mb-0" style="min-width: 320px;">
            <div class="judul-panel">
                <h3>Pengeluaran Terbaru</h3>
                <a href="{{ route('keuangan.pengeluaran') }}" class="tautan">Kelola</a>
            </div>

            @forelse ($pengeluaranTerbaru as $item)
                <div class="baris-data">
                    <div>
                        <strong>{{ $item->kategori }}</strong>
                        <div class="kecil">{{ $item->tanggal->translatedFormat('d M Y') }} — {{ $item->label_jenis }}</div>
                    </div>
                    <div class="angka">Rp {{ number_format($item->jumlah, 0, ',', '.') }}</div>
                </div>
            @empty
                <p class="redup">Belum ada pengeluaran tercatat.</p>
            @endforelse
        </div>

        <div class="kartu mb-0" style="min-width: 320px;">
            <div class="judul-panel">
                <h3>Pembelian Terbaru</h3>
                <a href="{{ route('pembelian.index') }}" class="tautan">Kelola</a>
            </div>

            @forelse ($pembelianTerbaru as $item)
                <div class="baris-data">
                    <div>
                        <strong>{{ $item->supplier ?: 'Tanpa supplier' }}</strong>
                        <div class="kecil">{{ $item->nomor_pembelian }} — {{ $item->tanggal->translatedFormat('d M Y') }}</div>
                    </div>
                    <div class="angka">Rp {{ number_format($item->total, 0, ',', '.') }}</div>
                </div>
            @empty
                <p class="redup">Belum ada pembelian tercatat.</p>
            @endforelse
        </div>
    </div>

    <div class="flex">
        <a href="{{ route('keuangan.pengeluaran') }}" class="tombol abu">Catat Pengeluaran</a>
        <a href="{{ route('pembelian.create') }}" class="tombol abu">Catat Pembelian</a>
        <a href="{{ route('keuangan.tutup-buku') }}" class="tombol abu">Tutup Buku Hari Ini</a>
    </div>
@endsection
