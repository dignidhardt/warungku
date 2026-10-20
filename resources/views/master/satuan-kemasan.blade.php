@extends('layouts.app')

@section('judul', 'Satuan Kemasan')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Satuan Kemasan</h2>
            <p>Opsi satuan kemasan (karung, dus, renceng). Boleh kosong kalau produk dijual per satuan dasar.</p>
        </div>
    </div>

    <div class="kartu">
        <h3>Tambah Satuan Kemasan</h3>

        @include('master._form-sederhana', [
            'routeSimpan' => 'master.satuan-kemasan.simpan',
            'judul' => '+ Tambah Satuan Kemasan',
            'contoh' => 'karung, dus, renceng, paket',
        ])
    </div>

    <div class="kartu">
        <h3>Daftar Satuan Kemasan</h3>

        @if ($daftar->isEmpty())
            <p class="kosong">Belum ada satuan kemasan. Tambahkan dulu supaya form produk punya pilihan.</p>
        @else
            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th class="tengah">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daftar as $item)
                            <tr>
                                <td><strong>{{ $item->nama }}</strong></td>
                                <td class="tengah">
                                    <div class="flex" style="justify-content:center;">
                                        <form action="{{ route('master.satuan-kemasan.ubah', $item) }}" method="POST"
                                              class="flex" style="gap:6px; justify-content:center;">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="nama" value="{{ $item->nama }}" required
                                                   maxlength="50" aria-label="Nama satuan kemasan"
                                                   style="height:30px; padding:0 8px; font-size:12px; border:1px solid #cbd5e1; border-radius:5px; width:160px;">
                                            <button type="submit" class="tombol kecil">Simpan</button>
                                        </form>

                                        <form action="{{ route('master.satuan-kemasan.hapus', $item) }}" method="POST"
                                              onsubmit="return confirm('Hapus satuan kemasan {{ $item->nama }}?')">
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
                <span>Menampilkan {{ $daftar->firstItem() ?? 0 }}–{{ $daftar->lastItem() ?? 0 }} dari {{ $daftar->total() }} satuan kemasan</span>
                <div class="tautan">{{ $daftar->links('pagination::warungku') }}</div>
            </div>
        @endif
    </div>
@endsection
