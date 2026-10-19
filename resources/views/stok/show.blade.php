@extends('layouts.app')

@section('judul', 'Detail Produk')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>{{ $produk->nama_produk }}</h2>
            <p>Barcode {{ $produk->barcode }} · {{ $produk->kategori }}</p>
        </div>
        <div class="flex">
            <a href="{{ route('stok.index') }}" class="tombol abu">Kembali</a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('stok.edit', $produk) }}" class="tombol">Ubah Produk</a>
            @endif
        </div>
    </div>

    <div class="baris-statistik">
        <div class="statistik {{ $produk->stok <= 0 ? 'merah' : ($produk->status_stok === 'menipis' ? 'kuning' : 'hijau') }}">
            <div class="label">Stok Saat Ini</div>
            <div class="nilai">{{ $produk->stok }} <span class="satuan">pcs</span></div>
        </div>
        <div class="statistik">
            <div class="label">Harga Beli</div>
            <div class="nilai">Rp {{ number_format($produk->harga_beli, 0, ',', '.') }}</div>
        </div>
        <div class="statistik">
            <div class="label">Harga Jual</div>
            <div class="nilai">Rp {{ number_format($produk->harga_jual, 0, ',', '.') }}</div>
        </div>
        <div class="statistik hijau">
            <div class="label">Margin per Unit</div>
            <div class="nilai">Rp {{ number_format($produk->margin, 0, ',', '.') }}</div>
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <div class="baris-statistik">
            <div class="kartu mb-0" style="min-width: 300px;">
                <h3>Tambah Stok (Barang Datang)</h3>
                <form action="{{ route('stok.tambah', $produk) }}" method="POST">
                    @csrf
                    <div class="bidang">
                        <label for="jumlah">Jumlah masuk</label>
                        <input type="number" id="jumlah" name="jumlah" min="1" required placeholder="contoh: 24">
                    </div>
                    <div class="bidang">
                        <label for="keterangan-tambah">Keterangan</label>
                        <input type="text" id="keterangan-tambah" name="keterangan" placeholder="contoh: kiriman supplier">
                    </div>
                    <button type="submit" class="tombol hijau blok">Tambah Stok</button>
                </form>
            </div>

            <div class="kartu mb-0" style="min-width: 300px;">
                <h3>Penyesuaian Stok (Stok Opname)</h3>
                <form action="{{ route('stok.sesuaikan', $produk) }}" method="POST">
                    @csrf
                    <div class="bidang">
                        <label for="stok_baru">Jumlah stok sebenarnya</label>
                        <input type="number" id="stok_baru" name="stok_baru" min="0" required
                               value="{{ $produk->stok }}">
                    </div>
                    <div class="bidang">
                        <label for="keterangan-sesuai">Alasan penyesuaian</label>
                        <input type="text" id="keterangan-sesuai" name="keterangan" required
                               placeholder="contoh: rusak, hilang, salah hitung">
                        <div class="bantuan">Wajib diisi agar perubahan stok bisa diaudit.</div>
                    </div>
                    <button type="submit" class="tombol blok">Simpan Penyesuaian</button>
                </form>
            </div>
        </div>
    @endif

    <div class="kartu">
        <h3>Riwayat Stok</h3>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Jenis</th>
                        <th class="tengah">Jumlah</th>
                        <th class="tengah">Stok Sesudah</th>
                        <th>Keterangan</th>
                        <th>Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mutasis as $mutasi)
                        <tr>
                            <td>{{ $mutasi->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @php
                                    $warnaJenis = [
                                        'masuk' => 'hijau',
                                        'keluar' => 'merah',
                                        'penyesuaian' => 'kuning',
                                    ][$mutasi->jenis] ?? 'abu';
                                @endphp
                                <span class="lencana {{ $warnaJenis }}">{{ $mutasi->label }}</span>
                            </td>
                            <td class="tengah"><strong>{{ $mutasi->jumlah }}</strong></td>
                            <td class="tengah">{{ $mutasi->stok_sesudah }}</td>
                            <td class="kecil">{{ $mutasi->keterangan ?? '—' }}</td>
                            <td class="kecil">{{ $mutasi->user->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="kosong">Belum ada mutasi stok tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginasi">
            <span>Total {{ $mutasis->total() }} mutasi</span>
            <div class="tautan">{{ $mutasis->links('pagination::warungku') }}</div>
        </div>
    </div>
@endsection
