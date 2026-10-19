@extends('layouts.app')

@section('judul', 'Produk & Stok')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Produk &amp; Stok</h2>
            <p>Diurutkan dari stok paling sedikit. {{ $produk->total() }} produk terdaftar.</p>
        </div>

        @if (auth()->user()->isAdmin())
            <a href="{{ route('stok.create') }}" class="tombol">+ Tambah Produk</a>
        @endif
    </div>

    <div class="baris-statistik">
        <div class="statistik merah">
            <div class="label">Stok Habis</div>
            <div class="nilai">{{ $totalHabis }} <span class="satuan">produk</span></div>
        </div>
        <div class="statistik kuning">
            <div class="label">Stok Menipis</div>
            <div class="nilai">{{ $totalMenipis }} <span class="satuan">produk</span></div>
        </div>
    </div>

    <div class="kartu">
        <form action="{{ route('stok.index') }}" method="GET" class="baris-filter">
            <div class="bidang cari">
                <label for="cari">Cari produk</label>
                <input type="text" id="cari" name="cari" value="{{ $keyword }}"
                       placeholder="Nama, barcode, atau kategori...">
            </div>

            <div class="bidang">
                <label for="status">Status stok</label>
                <select id="status" name="status">
                    <option value="">Semua</option>
                    <option value="menipis" @selected($status === 'menipis')>Menipis</option>
                    <option value="habis" @selected($status === 'habis')>Habis</option>
                </select>
            </div>

            <button type="submit" class="tombol">Cari</button>

            @if ($keyword !== '' || $status)
                <a href="{{ route('stok.index') }}" class="tombol abu">Reset</a>
            @endif
        </form>
    </div>

    <div class="kartu">
        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Barcode</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th class="angka">Harga Beli</th>
                        <th class="angka">Harga Jual</th>
                        <th class="tengah">Stok</th>
                        <th class="tengah">Status</th>
                        <th class="tengah">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($produk as $item)
                        <tr>
                            <td class="kecil">{{ $item->barcode }}</td>
                            <td>
                                <strong>{{ $item->nama_produk }}</strong>
                            </td>
                            <td>{{ $item->kategori }}</td>
                            <td class="angka">Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</td>
                            <td class="angka">Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                            <td class="tengah"><strong>{{ $item->stok }}</strong></td>
                            <td class="tengah">
                                @php
                                    $warna = ['aman' => 'hijau', 'menipis' => 'kuning', 'habis' => 'merah'][$item->status_stok];
                                @endphp
                                <span class="lencana {{ $warna }}">{{ $item->status_stok }}</span>
                            </td>
                            <td class="tengah">
                                <div class="flex" style="justify-content:center;">
                                    <a href="{{ route('stok.show', $item) }}" class="tombol kecil abu">Detail</a>

                                    @if (auth()->user()->isAdmin())
                                        <a href="{{ route('stok.edit', $item) }}" class="tombol kecil">Ubah</a>

                                        <form action="{{ route('stok.destroy', $item) }}" method="POST"
                                              onsubmit="return confirm('Hapus produk {{ $item->nama_produk }}? Riwayat stoknya ikut terhapus.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tombol kecil merah">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="kosong">
                                Tidak ada produk yang cocok.
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('stok.create') }}">Tambah produk baru</a>.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginasi">
            <span>Menampilkan {{ $produk->firstItem() ?? 0 }}–{{ $produk->lastItem() ?? 0 }} dari {{ $produk->total() }} produk</span>
            <div class="tautan">{{ $produk->links('pagination::warungku') }}</div>
        </div>
    </div>
@endsection
