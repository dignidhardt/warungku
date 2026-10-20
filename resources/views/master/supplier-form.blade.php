@extends('layouts.app')

@section('judul', $supplier->exists ? 'Ubah Supplier' : 'Tambah Supplier')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>{{ $supplier->exists ? 'Ubah Supplier' : 'Tambah Supplier' }}</h2>
            <p>
                @if ($supplier->exists)
                    Kode supplier: <strong>{{ $supplier->kode }}</strong>
                @else
                    Kode supplier dibuat otomatis (SUP-001, SUP-002, dst).
                @endif
            </p>
        </div>

        <a href="{{ route('master.supplier') }}" class="tombol abu">Kembali</a>
    </div>

    <div class="kartu">
        <form action="{{ $supplier->exists ? route('master.supplier.update', $supplier) : route('master.supplier.store') }}"
              method="POST">
            @csrf
            @if ($supplier->exists)
                @method('PUT')
            @endif

            <div class="baris-form">
                <div class="bidang">
                    <label for="nama">Nama Supplier</label>
                    <input type="text" id="nama" name="nama" required
                           value="{{ old('nama', $supplier->nama) }}" placeholder="mis. Toko Sembako Jaya">
                </div>

                <div class="bidang">
                    <label for="telepon">Telepon</label>
                    <input type="text" id="telepon" name="telepon"
                           value="{{ old('telepon', $supplier->telepon) }}" placeholder="mis. 0812-3456-7890">
                </div>
            </div>

            <div class="baris-form">
                <div class="bidang">
                    <label for="alamat">Alamat</label>
                    <input type="text" id="alamat" name="alamat"
                           value="{{ old('alamat', $supplier->alamat) }}" placeholder="mis. Pasar Indomaret, Block C">
                </div>
            </div>

            <div class="flex">
                <button type="submit" class="tombol">
                    {{ $supplier->exists ? 'Simpan Perubahan' : 'Simpan Supplier' }}
                </button>
                <a href="{{ route('master.supplier') }}" class="tombol abu">Batal</a>
            </div>
        </form>
    </div>
@endsection
