@extends('layouts.app')

@section('judul', 'Ubah Produk')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Ubah Produk</h2>
            <p>{{ $produk->nama_produk }} · stok saat ini {{ $produk->stok }} pcs</p>
        </div>
        <div class="flex">
            <a href="{{ route('stok.show', $produk) }}" class="tombol abu">Detail</a>
            <a href="{{ route('stok.index') }}" class="tombol abu">Kembali</a>
        </div>
    </div>

    <div class="kartu">
        @include('stok._form')
    </div>
@endsection
