@extends('layouts.app')

@section('judul', 'Kategori')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Kategori</h2>
            <p>Opsi kategori untuk form produk. Kode Barang di sini jadi awalan Kode Barang produk.</p>
        </div>
    </div>

    <div class="kartu">
        <h3>Tambah Kategori</h3>

        <form action="{{ route('master.kategori.simpan') }}" method="POST" class="baris-filter">
            @csrf

            <div class="bidang cari">
                <label for="nama">Nama Kategori</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required
                       placeholder="mis. Makanan">
            </div>

            <div class="bidang">
                <label for="kode_barang">Kode Barang</label>
                <input type="text" id="kode_barang" name="kode_barang" value="{{ old('kode_barang') }}"
                       maxlength="3" placeholder="AAA" pattern="[A-Za-z0-9]{3}">
                <div class="bantuan">3 karakter (angka &amp; huruf). Kosongkan untuk dibuat otomatis.</div>
            </div>

            <button type="submit" class="tombol">+ Tambah Kategori</button>
        </form>
    </div>

    <div class="kartu">
        <h3>Daftar Kategori</h3>

        @if ($daftar->isEmpty())
            <p class="kosong">Belum ada kategori. Tambahkan dulu supaya form produk punya pilihan.</p>
        @else
            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kode Barang</th>
                            <th class="tengah">Produk</th>
                            <th class="tengah">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daftar as $item)
                            <tr>
                                <td><strong>{{ $item->nama }}</strong></td>
                                <td>
                                    <span class="lencana biru">{{ $item->kode_barang }}</span>
                                </td>
                                <td class="tengah">{{ $item->produk_count }}</td>
                                <td class="tengah">
                                    <div class="flex" style="justify-content:center;">
                                        <a href="{{ route('stok.create', ['kategori_id' => $item->id]) }}" class="tombol kecil abu">
                                            Tambah Produk
                                        </a>

                                        <form action="{{ route('master.kategori.ubah', $item) }}" method="POST"
                                              class="flex" style="gap:6px; justify-content:center;">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="nama" value="{{ $item->nama }}" required
                                                   maxlength="100" aria-label="Nama kategori"
                                                   style="height:30px; padding:0 8px; font-size:12px; border:1px solid #cbd5e1; border-radius:5px; width:140px;">
                                            <input type="text" name="kode_barang" value="{{ $item->kode_barang }}"
                                                   maxlength="3" pattern="[A-Za-z0-9]{3}" aria-label="Kode Barang"
                                                   style="height:30px; padding:0 8px; font-size:12px; border:1px solid #cbd5e1; border-radius:5px; width:80px;">
                                            <button type="submit" class="tombol kecil">Simpan</button>
                                        </form>

                                        <form action="{{ route('master.kategori.hapus', $item) }}" method="POST"
                                              onsubmit="return confirm('Hapus kategori {{ $item->nama }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tombol kecil merah">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="paginasi">
                <span>Menampilkan {{ $daftar->firstItem() ?? 0 }}–{{ $daftar->lastItem() ?? 0 }} dari {{ $daftar->total() }} kategori</span>
                <div class="tautan">{{ $daftar->links('pagination::warungku') }}</div>
            </div>
        @endif
    </div>
@endsection
