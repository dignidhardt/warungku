@extends('layouts.app')

@section('judul', 'Manajemen Karyawan')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Manajemen Karyawan</h2>
            <p>{{ $users->count() }} akun terdaftar. Admin bisa menambah, mengubah, dan menghapus akun.</p>
        </div>
        <a href="{{ route('users.create') }}" class="tombol">+ Tambah Akun</a>
    </div>

    <div class="kartu">
        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th class="tengah">Hak Akses</th>
                        <th class="tengah">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                @if ($user->id === auth()->id())
                                    <span class="lencana abu" style="margin-left:6px;">Anda</span>
                                @endif
                            </td>
                            <td>{{ $user->username }}</td>
                            <td class="kecil">{{ $user->email }}</td>
                            <td class="tengah">
                                <span class="lencana {{ $user->isAdmin() ? 'biru' : 'abu' }}">{{ $user->role }}</span>
                            </td>
                            <td class="tengah">
                                <div class="flex" style="justify-content:center;">
                                    <a href="{{ route('users.edit', $user) }}" class="tombol kecil">Ubah</a>

                                    @if ($user->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $user) }}" method="POST"
                                              onsubmit="return confirm('Hapus akun {{ $user->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tombol kecil merah">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
