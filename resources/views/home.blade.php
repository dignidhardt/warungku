@extends('layouts.app')

@section('content')
<div style="padding: 30px; font-family: sans-serif; width: 100%;">
    
    <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px;">
        <h1 style="color: #1e293b; margin-top: 0; font-size: 24px;">Selamat Datang Kembali, {{ Auth::user()->name }}!</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Aplikasi kasir Toko Kelontong aktif. Pantau ringkasan tokomu hari ini.</p>
    </div>

    

    <div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
        
        <div style="flex: 1; min-width: 240px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); border-left: 5px solid #0d6efd;">
            <div style="font-size: 13px; color: #64748b; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Total Produk</div>
            <div style="font-size: 32px; font-weight: bold; color: #1e293b; margin-top: 8px;">
                {{ $totalProduk }} <span style="font-size: 16px; font-weight: normal; color: #64748b;">Item</span>
            </div>
        </div>

        <div style="flex: 1; min-width: 240px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); border-left: 5px solid #198754;">
            <div style="font-size: 13px; color: #64748b; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Transaksi Hari Ini</div>
            <div style="font-size: 32px; font-weight: bold; color: #1e293b; margin-top: 8px;">
                {{ $transaksiHariIni }} <span style="font-size: 16px; font-weight: normal; color: #64748b;">Nota</span>
            </div>
        </div>

        <div style="flex: 1; min-width: 240px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); border-left: 5px solid #ffc107;">
            <div style="font-size: 13px; color: #64748b; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Level Pengguna</div>
            <div style="font-size: 32px; font-weight: bold; color: {{ Auth::user()->role === 'admin' ? '#d97706' : '#0284c7' }}; margin-top: 8px; text-transform: capitalize;">
                {{ Auth::user()->role }}
            </div>
            <div style="font-size: 12px; color: #a0aec0; margin-top: 8px;">
                {{ Auth::user()->role === 'admin' ? 'Akses penuh ke sistem' : 'Akses terbatas untuk kasir' }}
            </div>
        </div>
    </div>

    <div class="card" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,.1);">
        <h3 style="margin-bottom: 20px; color: #1e293b;">Akses Cepat Aplikasi</h3>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            
            <a href="{{ route('transaksi.index') }}" style="text-decoration: none; display: flex; align-items: center; gap: 10px; background: #0d6efd; color: white; padding: 14px 22px; border-radius: 8px; font-weight: bold; font-size: 14px;">
                Mulai Transaksi
            </a>

            <a href="{{ route('stok.index') }}" style="text-decoration: none; display: flex; align-items: center; gap: 10px; background: #1e293b; color: white; padding: 14px 22px; border-radius: 8px; font-weight: bold; font-size: 14px;">
                Cek Stok
            </a>

@if(Auth::user()->role === 'admin')
    <a href="{{ route('users.index') }}" style="text-decoration: none; display: flex; align-items: center; gap: 10px; background: #6366f1; color: white; padding: 14px 22px; border-radius: 8px; font-weight: bold; font-size: 14px;">
        Kelola Karyawan
    </a>
@endif

<a href="{{ route('laporan.index') }}" style="text-decoration: none; display: flex; align-items: center; gap: 10px; background: #10b981; color: white; padding: 14px 22px; border-radius: 8px; font-weight: bold; font-size: 14px;">
    Laporan Keuangan
</a>
        </div>
    </div>

</div>
@endsection