@extends('layouts.app')

@section('judul', 'Ubah Akun')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Ubah Akun</h2>
            <p>{{ $user->name }} · {{ $user->username }}</p>
        </div>
        <a href="{{ route('users.index') }}" class="tombol abu">Kembali</a>
    </div>

    <div class="kartu">
        @include('users._form')
    </div>
@endsection
