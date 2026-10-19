@extends('layouts.app')

@section('judul', 'Tutup Buku')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Tutup Buku</h2>
            <p>Hitung uang fisik di akhir hari, lalu bandingkan dengan catatan sistem.</p>
        </div>
    </div>

    <div class="kartu tanpa-cetak">
        <form action="{{ route('keuangan.tutup-buku') }}" method="GET" class="baris-filter">
            <div class="bidang">
                <label for="tanggal">Tanggal</label>
                <input type="date" id="tanggal" name="tanggal" value="{{ $tanggal->toDateString() }}">
            </div>
            <button type="submit" class="tombol">Tampilkan</button>
        </form>
    </div>

    <div class="kartu">
        <h3>Tutup Buku {{ $tanggal->translatedFormat('d F Y') }}</h3>

        <div class="baris-statistik" style="margin-bottom: 16px;">
            <div class="statistik">
                <div class="label">Seharusnya Ada</div>
                <div class="nilai">Rp {{ number_format($kasSeharusnya, 0, ',', '.') }}</div>
                <div class="kecil">Penjualan tunai − retur − pengeluaran − prive</div>
            </div>

            <div class="kartu mb-0" style="min-width: 320px;">
                <h3>Dari mana angkanya</h3>
                @foreach ($rincian['total'] as $label => $nilai)
                    <div class="baris-data">
                        <div>{{ $label }}</div>
                        <div class="angka">
                            {{ $nilai < 0 ? '− Rp '.number_format(abs($nilai), 0, ',', '.') : 'Rp '.number_format($nilai, 0, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <form action="{{ route('keuangan.tutup-buku.simpan') }}" method="POST">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">

            <div class="baris-filter">
                <div class="bidang">
                    <label for="kas_fisik">Uang Fisik (Rp)</label>
                    <input type="number" id="kas_fisik" name="kas_fisik" min="0" step="100"
                           value="{{ old('kas_fisik') }}" required autofocus>
                </div>

                <div class="bidang" style="flex: 1 1 100%;">
                    <label for="catatan">Catatan / Alasan Selisih</label>
                    <input type="text" id="catatan" name="catatan"
                           placeholder="Wajib diisi kalau uang tidak cocok" value="{{ old('catatan') }}">
                </div>
            </div>

            <div class="flex" style="margin-top: 12px;">
                <button type="submit" class="tombol">Simpan Tutup Buku</button>
            </div>
        </form>
    </div>

    <div class="kartu">
        <h3>Riwayat Tutup Buku</h3>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Seharusnya</th>
                        <th>Fisik</th>
                        <th>Selisih</th>
                        <th>Catatan</th>
                        <th>Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayat as $row)
                        <tr>
                            <td>{{ $row->tanggal->translatedFormat('d M Y') }}</td>
                            <td class="angka">Rp {{ number_format($row->kas_seharusnya, 0, ',', '.') }}</td>
                            <td class="angka">Rp {{ number_format($row->kas_fisik, 0, ',', '.') }}</td>
                            <td class="angka {{ 'selisih-'.$row->status_selisih }}">
                                @if ($row->selisih > 0)
                                    + Rp {{ number_format($row->selisih, 0, ',', '.') }}
                                @elseif ($row->selisih < 0)
                                    − Rp {{ number_format(abs($row->selisih), 0, ',', '.') }}
                                @else
                                    Rp 0
                                @endif
                            </td>
                            <td class="kecil">{{ $row->catatan ?: '—' }}</td>
                            <td class="kecil">{{ $row->user->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="kosong">Belum ada tutup buku tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="pesan info tanpa-cetak">
        <strong>Kenapa selisihnya disimpan, bukan dibulatkan?</strong> Kalau uang kurang Rp 5.000
        setiap hari, dan sistem cuma menampilkan "cocok", uang yang hilang tidak akan pernah terlihat.
        Selisih yang dicatat apa adanya bisa ditelusuri ke sumbernya.
    </div>
@endsection
