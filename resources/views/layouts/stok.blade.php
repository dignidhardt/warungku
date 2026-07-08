@extends('layouts.app')

@section('content')
<style>
    /* Trik mendeteksi container bawaan dan memaksa ukurannya jebol ke kanan */
    .card, 
    .card-body,
    div[class*="container"], 
    div[class*="col"] {
        max-width: 100% !important;
        width: 100% !important;
    }
</style>

<div style="width: 100%; padding-right: 30px; box-sizing: border-box;">
    
    @if(session('success'))
        <div style="background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-family: sans-serif; font-weight: bold; border-left: 5px solid #198754; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background: #f8d7da; color: #842029; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-family: sans-serif; font-weight: bold; border-left: 5px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            ⚠️ {{ session('error') }}
        </div>
    @endif
    
    <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); width: 100%; box-sizing: border-box;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; flex-wrap: wrap; gap: 15px;">
            <h4 style="margin: 0; font-size: 18px; font-family: sans-serif; color: #333; font-weight: bold;">Daftar Stok Produk</h4>
            
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <form action="{{ route('stok.index') }}" method="GET" style="display: flex; gap: 5px; margin: 0;">
                    <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama atau barcode..." style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; width: 220px; font-family: sans-serif; box-sizing: border-box;">
                    
                    <button type="submit" style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 14px; font-family: sans-serif;">
                        Cari
                    </button>
                    
                    @if(request('cari'))
                        <a href="{{ route('stok.index') }}" style="background: #e2e8f0; color: #475569; padding: 8px 12px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 14px; font-family: sans-serif; display: flex; align-items: center;">
                            ❌ Reset
                        </a>
                    @endif
                </form>

                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('stok.create') }}" style="background: #0d6efd; color: white; padding: 9px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; font-family: sans-serif; font-size: 14px; box-shadow: 0 2px 4px rgba(13,110,253,0.2); white-space: nowrap;">
                        + Tambah Produk
                    </a>
                @endif
            </div>
        </div>

        <div style="overflow-x: auto; width: 100%;">
            <table style="width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 14px;">
                <thead>
                    <tr style="background: #0d6efd; color: white;">
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: center; width: 50px;">No</th>
                        <th style="border: 1px solid #ddd; padding: 12px;">Barcode</th>
                        <th style="border: 1px solid #ddd; padding: 12px;">Nama Produk</th>
                        <th style="border: 1px solid #ddd; padding: 12px;">Kategori</th>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: right;">Harga Beli</th>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: right;">Harga Jual</th>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: center; width: 80px;">Stok</th>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: center; width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($stok as $item)
                    <tr style="background: #fff;">
                        <td style="border: 1px solid #ddd; padding: 12px; text-align: center;">{{ $loop->iteration }}</td>
                        <td style="border: 1px solid #ddd; padding: 12px;">{{ $item->barcode }}</td>
                        <td style="border: 1px solid #ddd; padding: 12px;">{{ $item->nama_produk }}</td>
                        <td style="border: 1px solid #ddd; padding: 12px;">{{ $item->kategori }}</td>
                        <td style="border: 1px solid #ddd; padding: 12px; text-align: right;">Rp {{ number_format($item->harga_beli,0,',','.') }}</td>
                        <td style="border: 1px solid #ddd; padding: 12px; text-align: right;">Rp {{ number_format($item->harga_jual,0,',','.') }}</td>
                        
                        <td style="border: 1px solid #ddd; padding: 12px; text-align: center; font-weight: bold; color: {{ $item->stok <= 3 ? 'red' : '#333' }};">
                            {{ $item->stok }}
                            @if($item->stok == 0)
                                <span style="display:block; font-size:10px; color:red; font-weight:normal;">Habis</span>
                            @endif
                        </td>

                        <td style="border: 1px solid #ddd; padding: 12px; text-align: center;">
                            @if(Auth::user()->role === 'admin')
                                <a href="{{ route('stok.edit', $item->id) }}" style="background: orange; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; margin-right: 5px; font-size: 13px;">Edit</a>
                                <form action="{{ route('stok.destroy', $item->id) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button style="background: red; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;" onclick="return confirm('Hapus produk ini?')">Hapus</button>
                                </form>
                            @else
                                <span style="color: #94a3b8; font-style: italic; font-size: 12px; background: #f1f5f9; padding: 4px 8px; border-radius: 4px;">🔒 Terkunci</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="border: 1px solid #ddd; padding: 25px; text-align: center; color: #666; font-style: italic;">
                            @if(request('cari'))
                                Produk dengan kata kunci "{{ request('cari') }}" tidak ditemukan.
                            @else
                                Belum ada data produk. Silakan klik tombol "+ Tambah Produk" untuk menginput data baru.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection