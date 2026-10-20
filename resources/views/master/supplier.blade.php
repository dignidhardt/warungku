@extends('layouts.app')

@section('judul', 'Supplier')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Supplier</h2>
            <p>Daftar supplier barang dagangan. Dipakai otomatis di form Pembelian.</p>
        </div>

        <a href="{{ route('master.supplier.create') }}" class="tombol">+ Tambah Supplier</a>
    </div>

    <div class="kartu">
        <form action="{{ route('master.supplier') }}" method="GET" class="baris-filter">
            <div class="bidang cari">
                <label for="cari">Cari</label>
                <input type="text" id="cari" name="cari" value="{{ $cari }}"
                       placeholder="Nama, kode, atau telepon supplier">
            </div>

            <button type="submit" class="tombol">Cari</button>

            @if ($cari !== '')
                <a href="{{ route('master.supplier') }}" class="tombol abu">Reset</a>
            @endif
        </form>
    </div>

    <div class="kartu">
        <h3>Daftar Supplier</h3>

        @if ($daftar->isEmpty())
            <p class="kosong">
                Belum ada supplier. Tambahkan dulu supaya form produk punya pilihan.
            </p>
        @else
            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Telepon</th>
                            <th>Alamat</th>
                            <th class="tengah">Produk</th>
                            <th class="tengah">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daftar as $item)
                            <tr>
                                <td>
                                    <span class="lencana biru">{{ $item->kode }}</span>
                                </td>
                                <td><strong>{{ $item->nama }}</strong></td>
                                <td class="kecil">{{ $item->telepon ?: '—' }}</td>
                                <td class="kecil">{{ $item->alamat ?: '—' }}</td>
                                <td class="tengah">{{ $item->produk_count }}</td>
                                <td class="tengah">
                                    <div class="flex" style="justify-content:center;">
                                        <a href="{{ route('master.supplier.edit', $item) }}" class="tombol kecil abu">Ubah</a>

                                        <form action="{{ route('master.supplier.hapus', $item) }}" method="POST"
                                              onsubmit="return confirm('Hapus supplier {{ $item->nama }}?')">
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
                <span>Menampilkan {{ $daftar->firstItem() ?? 0 }}–{{ $daftar->lastItem() ?? 0 }} dari {{ $daftar->total() }} supplier</span>
                <div class="tautan">{{ $daftar->links('pagination::warungku') }}</div>
            </div>
        @endif
    </div>
@endsection
