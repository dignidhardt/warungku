@extends('layouts.app')

@section('judul', 'Tambah Akun')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Tambah Akun Karyawan</h2>
            <p>Akun baru langsung bisa dipakai login.</p>
        </div>
        <a href="{{ route('users.index') }}" class="tombol abu">Kembali</a>
    </div>

    <div class="kartu">
        @include('users._form')
    </div>
@endsection
