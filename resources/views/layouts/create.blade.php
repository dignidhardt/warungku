@extends('layouts.app')

@section('content')
<style>
    .card, 
    .card-body,
    div[class*="container"], 
    div[class*="col"] {
        max-width: 100% !important;
        width: 100% !important;
    }
</style>

<div style="width: 100%; padding-right: 30px; box-sizing: border-box;">
    
    <div class="card" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); width: 100%; box-sizing: border-box;">
        <h4 style="margin-bottom: 20px; font-family: sans-serif; color: #333; font-weight: bold;">Tambah Produk Baru</h4>

        <form action="{{ route('stok.store') }}" method="POST">
            @csrf
            
            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold; font-family: sans-serif;">Barcode</label>
                <input type="text" name="barcode" required style="width:100%; height:40px; padding:10px; border:1px solid #ccc; border-radius:5px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold; font-family: sans-serif;">Nama Produk</label>
                <input type="text" name="nama_produk" required style="width:100%; height:40px; padding:10px; border:1px solid #ccc; border-radius:5px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 15px;">
    <label style="display:block; margin-bottom:5px; font-weight:bold; font-family: sans-serif;">Kategori</label>
    
    <input type="text" name="kategori" list="kategori_list" required placeholder="Ketik atau pilih kategori..." style="width:100%; height:40px; padding:10px; border:1px solid #ccc; border-radius:5px; box-sizing: border-box;">
    
    <datalist id="kategori_list">
        <option value="Makanan">
        <option value="Minuman">
        <option value="Snack">
        <option value="Lainnya">
    </datalist>
</div>
            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold; font-family: sans-serif;">Harga Beli</label>
                <input type="number" name="harga_beli" required style="width:100%; height:40px; padding:10px; border:1px solid #ccc; border-radius:5px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold; font-family: sans-serif;">Harga Jual</label>
                <input type="number" name="harga_jual" required style="width:100%; height:40px; padding:10px; border:1px solid #ccc; border-radius:5px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold; font-family: sans-serif;">Stok</label>
                <input type="number" name="stok" required style="width:100%; height:40px; padding:10px; border:1px solid #ccc; border-radius:5px; box-sizing: border-box;">
            </div>

            <button type="submit" style="background: #0d6efd; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-family: sans-serif; font-size: 14px;">
                Simpan Produk
            </button>
            
            <a href="{{ route('stok.index') }}" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin-left: 10px; font-weight: bold; font-family: sans-serif; font-size: 14px; display: inline-block;">
                Kembali
            </a>
        </form>
    </div>
</div>
@endsection