@extends('layouts.app')

@section('judul', 'Pengeluaran')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Pengeluaran</h2>
            <p>Catat biaya operasional warung dan prive pemilik.</p>
        </div>
    </div>

    <div class="kartu tanpa-cetak">
        <form action="{{ route('keuangan.pengeluaran') }}" method="POST">
            @csrf

            <div class="baris-filter">
                <div class="bidang">
                    <label for="jenis">Jenis</label>
                    <select id="jenis" name="jenis" required>
                        <option value="operasional">Operasional (bbiaya usaha)</option>
                        <option value="prive">Prive (pribadi pemilik)</option>
                        <option value="lain">Lain-lain</option>
                    </select>
                </div>

                <div class="bidang">
                    <label for="kategori">Kategori</label>
                    <input type="text" id="kategori" name="kategori" list="daftar-kategori"
                           placeholder="Pilih atau ketik" required>
                    <datalist id="daftar-kategori">
                        @foreach ($kategori as $k)
                            <option value="{{ $k }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <div class="bidang">
                    <label for="tanggal">Tanggal</label>
                    <input type="date" id="tanggal" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" required>
                </div>

                <div class="bidang">
                    <label for="jumlah">Jumlah (Rp)</label>
                    <input type="number" id="jumlah" name="jumlah" min="1" step="100" required>
                </div>
            </div>

            <div class="bidang" style="width: 100%; margin-top: 10px;">
                <label for="keterangan">Keterangan</label>
                <input type="text" id="keterangan" name="keterangan" placeholder="Opsional, mis. listrik bulan ini">
            </div>

            <div class="flex" style="margin-top: 12px;">
                <button type="submit" class="tombol">Simpan Pengeluaran</button>
            </div>
        </form>
    </div>

    <div class="kartu">
        <h3>Riwayat — {{ \Illuminate\Support\Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y') }}</h3>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Jenis</th>
                        <th>Keterangan</th>
                        <th class="angka">Jumlah</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pengeluarans as $item)
                        <tr>
                            <td>{{ $item->tanggal->translatedFormat('d M Y') }}</td>
                            <td>{{ $item->kategori }}</td>
                            <td>
                                <span class="lencana {{ $item->jenis === 'prive' ? 'kuning' : 'abu' }}">
                                    {{ $item->label_jenis }}
                                </span>
                            </td>
                            <td class="kecil">{{ $item->keterangan ?: '—' }}</td>
                            <td class="angka">Rp {{ number_format($item->jumlah, 0, ',', '.') }}</td>
                            <td class="kanan">
                                <form action="{{ route('keuangan.pengeluaran.hapus', $item) }}" method="POST"
                                      onsubmit="return confirm('Hapus pengeluaran {{ $item->kategori }} Rp {{ number_format($item->jumlah, 0, ',', '.') }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tombol merah kecil">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="kosong">Belum ada pengeluaran pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $pengeluarans->links() }}
    </div>
@endsection
