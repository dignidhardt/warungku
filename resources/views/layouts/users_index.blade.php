@extends('layouts.app')

@section('content')
<style>
    .card, .card-body, div[class*="container"], div[class*="col"] {
        max-width: 100% !important;
        width: 100% !important;
    }
</style>

<div style="width: 100%; padding-right: 30px; box-sizing: border-box;">
    
@if(session('success'))
    <div class="alert-auto-hide" style="background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-family: sans-serif; font-weight: bold; border-left: 5px solid #198754;">
        ✅ {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert-auto-hide" style="background: #f8d7da; color: #842029; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-family: sans-serif; font-weight: bold; border-left: 5px solid #dc3545;">
        ⚠️ {{ session('error') }}
    </div>
@endif

    <div style="display: flex; gap: 25px; flex-wrap: wrap; font-family: sans-serif;">
        
        <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); flex: 1; min-width: 300px; box-sizing: border-box;">
            <h4 style="margin: 0 0 20px 0; font-size: 16px; color: #333; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 10px;">+ Tambah Karyawan Baru</h4>
            
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px; font-size: 13px; font-weight: bold; color: #555;">Nama Lengkap</label>
                    <input type="text" name="name" required style="width:100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px; font-size: 13px; font-weight: bold; color: #555;">Email Login</label>
                    <input type="email" name="email" required style="width:100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px; font-size: 13px; font-weight: bold; color: #555;">Password (Min. 8 Karakter)</label>
                    <input type="password" name="password" required style="width:100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom: 5px; font-size: 13px; font-weight: bold; color: #555;">Jabatan / Hak Akses</label>
                    <select name="role" required style="width:100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; background: white;">
                        <option value="kasir">Kasir (Terbatas)</option>
                        <option value="admin">Admin (Akses Penuh)</option>
                    </select>
                </div>

                <button type="submit" style="width:100%; background: #0d6efd; color: white; border: none; padding: 10px; border-radius: 5px; font-weight: bold; cursor: pointer;">
                    Simpan Akun
                </button>
            </form>
        </div>

        <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); flex: 2; min-width: 500px; box-sizing: border-box;">
            <h4 style="margin: 0 0 20px 0; font-size: 16px; color: #333; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 10px;">Daftar Pengguna Sistem</h4>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="background: #0d6efd; color: white;">
                            <th style="border: 1px solid #ddd; padding: 10px; text-align: center; width: 40px;">No</th>
                            <th style="border: 1px solid #ddd; padding: 10px;">Nama</th>
                            <th style="border: 1px solid #ddd; padding: 10px;">Email</th>
                            <th style="border: 1px solid #ddd; padding: 10px; text-align: center; width: 100px;">Hak Akses</th>
                            <th style="border: 1px solid #ddd; padding: 10px; text-align: center; width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $user)
                        <tr style="background: #fff;">
                            <td style="border: 1px solid #ddd; padding: 10px; text-align: center;">{{ $loop->iteration }}</td>
                            <td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">{{ $user->name }}</td>
                            <td style="border: 1px solid #ddd; padding: 10px; color: #555;">{{ $user->email }}</td>
                            <td style="border: 1px solid #ddd; padding: 10px; text-align: center;">
                                <span style="background: {{ $user->role === 'admin' ? '#ffeecb' : '#e1f5fe' }}; color: {{ $user->role === 'admin' ? '#b76e00' : '#0288d1' }}; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px; text-align: center;">
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button style="background: red; color: white; padding: 4px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;" onclick="return confirm('Hapus pengguna {{ $user->name }}?')">Hapus</button>
                                    </form>
                                @else
                                    <span style="color: #aaa; font-size: 12px; font-style: italic;">Anda (Aktif)</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
<script>
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert-auto-hide');
        alerts.forEach(function(alert) {
            alert.style.display = 'none';
        });
    }, 3000); 
</script>
@endsection