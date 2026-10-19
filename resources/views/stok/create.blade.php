@extends('layouts.app')

@section('judul', 'Tambah Produk')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Tambah Produk</h2>
            <p>Stok awal akan dicatat sebagai mutasi masuk.</p>
        </div>
        <a href="{{ route('stok.index') }}" class="tombol abu">Kembali</a>
    </div>

    <div class="kartu">
        @include('stok._form')
    </div>
@endsection
