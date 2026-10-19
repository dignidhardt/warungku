@extends('layouts.app')

@section('judul', 'Buku Kas')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Buku Kas</h2>
            <p>Seluruh uang masuk dan keluar pada periode
                {{ \Illuminate\Support\Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y') }}.</p>
        </div>

        <div class="flex">
            <button type="button" class="tombol abu" onclick="window.print()">Cetak</button>
        </div>
    </div>

    <div class="kartu tanpa-cetak">
        <form action="{{ route('keuangan.buku-kas') }}" method="GET" class="baris-filter">
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
            <div class="label">Uang Masuk</div>
            <div class="nilai">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</div>
        </div>
        <div class="statistik merah">
            <div class="label">Uang Keluar</div>
            <div class="nilai">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</div>
        </div>
        <div class="statistik {{ ($totalMasuk - $totalKeluar) >= 0 ? 'hijau' : 'merah' }}">
            <div class="label">Selisih (saldo periode)</div>
            <div class="nilai">Rp {{ number_format($totalMasuk - $totalKeluar, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="kartu">
        <h3>Rincian Arus Kas</h3>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Keterangan</th>
                        <th class="angka">Masuk</th>
                        <th class="angka">Keluar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($baris as $row)
                        <tr>
                            <td class="kecil">{{ \Illuminate\Support\Carbon::parse($row['tanggal'])->format('d/m H:i') }}</td>
                            <td>
                                {{ $row['keterangan'] }}
                                @if ($row['jenis'] === 'prive')
                                    <span class="lencana kuning">Prive</span>
                                @endif
                            </td>
                            <td class="angka">{{ $row['masuk'] > 0 ? 'Rp '.number_format($row['masuk'], 0, ',', '.') : '—' }}</td>
                            <td class="angka">{{ $row['keluar'] > 0 ? 'Rp '.number_format($row['keluar'], 0, ',', '.') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="kosong">Belum ada arus kas pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($baris->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="2" class="kanan">Total</td>
                            <td class="angka">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</td>
                            <td class="angka">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="pesan info tanpa-cetak">
        <strong>Catatan.</strong> Baris bertanda <span class="lencana kuning">Prive</span> adalah pengambilan
        uang pribadi pemilik. Uang itu keluar dari laci, tapi tidak dianggap biaya usaha — karena itu tidak
        mengurangi laba di Laporan Laba Rugi.
    </div>
@endsection
