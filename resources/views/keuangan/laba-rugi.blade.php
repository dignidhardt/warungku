@extends('layouts.app')

@section('judul', 'Laba Rugi')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Laporan Laba Rugi</h2>
            <p>Periode {{ \Illuminate\Support\Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y') }}.</p>
        </div>

        <div class="flex">
            <button type="button" class="tombol abu" onclick="window.print()">Cetak</button>
            <a href="{{ route('keuangan.index', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol abu">Kembali</a>
        </div>
    </div>

    <div class="kartu tanpa-cetak">
        <form action="{{ route('keuangan.laba-rugi') }}" method="GET" class="baris-filter">
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

    <div class="kartu">
        <h3>Perhitungan</h3>

        <div class="tabel-bungkus">
            <table class="tabel tabel-laporan">
                <tbody>
                    <tr>
                        <td>Penjualan</td>
                        <td class="angka">Rp {{ number_format($data['penjualan'], 0, ',', '.') }}</td>
                    </tr>
                    @if ($data['retur'] > 0)
                        <tr>
                            <td class="indent">Retur penjualan</td>
                            <td class="angka merah">− Rp {{ number_format($data['retur'], 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="tebal">
                        <td>Omzet bersih</td>
                        <td class="angka">Rp {{ number_format($data['omzet_bersih'], 0, ',', '.') }}</td>
                    </tr>

                    <tr>
                        <td class="indent">Harga pokok penjualan</td>
                        <td class="angka merah">− Rp {{ number_format($data['hpp'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="tebal">
                        <td>Laba kotor</td>
                        <td class="angka">Rp {{ number_format($data['laba_kotor'], 0, ',', '.') }}</td>
                    </tr>

                    @forelse ($data['per_kategori'] as $kategori => $jumlah)
                        <tr>
                            <td class="indent">{{ $kategori }}</td>
                            <td class="angka merah">− Rp {{ number_format($jumlah, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="indent">Belum ada biaya operasional</td>
                            <td class="angka">—</td>
                        </tr>
                    @endforelse

                    <tr class="tebal">
                        <td>Total biaya operasional</td>
                        <td class="angka merah">− Rp {{ number_format($data['biaya_operasional'], 0, ',', '.') }}</td>
                    </tr>

                    <tr class="tebal {{ $data['laba_bersih'] >= 0 ? 'hijau' : 'merah' }}">
                        <td>LABA BERSIH</td>
                        <td class="angka">Rp {{ number_format($data['laba_bersih'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="baris-statistik">
        <div class="kartu mb-0" style="min-width: 340px;">
            <h3>Catatan</h3>
            <ul class="daftar-catatan">
                <li>
                    <strong>Harga pokok</strong> memakai harga beli yang tersimpan di tiap nota, bukan
                    harga beli produk saat ini. Jadi laporan bulan lalu tidak berubah kalau harga kulakan naik.
                </li>
                <li>
                    <strong>Prive Rp {{ number_format($data['prive'], 0, ',', '.') }}</strong> tidak dihitung
                    biaya usaha — itu pengambilan uang pribadi pemilik, bukan ongkos menjalankan warung.
                </li>
                <li>
                    <strong>Pembelian Rp {{ number_format($data['pembelian'], 0, ',', '.') }}</strong> juga
                    tidak muncul di sini: pembelian menambah persediaan (aset), sedangkan yang jadi biaya
                    adalah harga pokok barang yang benar-benar terjual.
                </li>
            </ul>
        </div>

        <div class="kartu mb-0" style="min-width: 300px;">
            <h3>Ringkas</h3>
            <div class="baris-data">
                <div>Omzet bersih</div>
                <div class="angka">Rp {{ number_format($data['omzet_bersih'], 0, ',', '.') }}</div>
            </div>
            <div class="baris-data">
                <div>Margin kotor</div>
                <div class="angka">
                    {{ $data['omzet_bersih'] > 0 ? round($data['laba_kotor'] / $data['omzet_bersih'] * 100, 1) : 0 }}%
                </div>
            </div>
            <div class="baris-data">
                <div>Margin bersih</div>
                <div class="angka">
                    {{ $data['omzet_bersih'] > 0 ? round($data['laba_bersih'] / $data['omzet_bersih'] * 100, 1) : 0 }}%
                </div>
            </div>
            <div class="baris-data">
                <div>Jumlah nota</div>
                <div class="angka">{{ $data['jumlah_transaksi'] }}</div>
            </div>
        </div>
    </div>
@endsection
