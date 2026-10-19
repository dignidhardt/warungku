<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stok; // Pastikan model Stok di-import dengan benar

class StokController extends Controller
{
    // 1. Menampilkan halaman utama daftar stok (URUTAN DARI STOK TERSEDIKIT + PENCARIAN)
    public function index(Request $request)
    {
        // Ambil data kata kunci 'cari' dari form input
        $keyword = $request->get('cari');

        // Buat query dasar dari model Stok
        $query = Stok::query();

        // Jika user mengetik sesuatu di kolom pencarian, saring datanya
        if (!empty($keyword)) {
            $query->where('nama_produk', 'LIKE', "%{$keyword}%")
                  ->orWhere('barcode', 'LIKE', "%{$keyword}%")
                  ->orWhere('kategori', 'LIKE', "%{$keyword}%");
        }

        // BERHASIL DIUBAH: Diurutkan dari jumlah stok terkecil (0, 1, 2...) ke terbesar
        $stok = $query->orderBy('stok', 'asc')->get(); 
        
        // Mengarah ke resources/views/layouts/stok.blade.php
        return view('layouts.stok', compact('stok')); 
    }

    // 2. Menampilkan halaman form tambah produk
    public function create()
    {
        return view('layouts.create'); 
    }

    // 3. Memproses penyimpanan data dari form input baru
    public function store(Request $request)
    {
        $request->validate([
            'barcode' => 'required',
            'nama_produk' => 'required',
            'kategori' => 'required',
            'harga_beli' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            'stok' => 'required|integer',
        ]);

        Stok::create($request->all());

        return redirect()->route('stok.index')->with('success', 'Produk berhasil ditambahkan!');
    }

    // 5. MEMPROSES UPDATE DATA PRODUK
    public function update(Request $request, $id)
    {
        $request->validate([
            'barcode' => 'required',
            'nama_produk' => 'required',
            'kategori' => 'required',
            'harga_beli' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            'stok' => 'required|integer',
        ]);

        $stok = Stok::findOrFail($id);
        $stok->update($request->all());

        return redirect()->route('stok.index')->with('success', 'Produk berhasil diperbarui!');
    }

    // 6. MEMPROSES PENGHAPUSAN DATA
    public function destroy($id)
    {
        // Mencari data berdasarkan ID, jika tidak ada langsung memicu error 404
        $stok = Stok::findOrFail($id); 
        
        // Menghapus data dari database
        $stok->delete(); 

        // Mengembalikan tampilan ke halaman utama dengan pesan sukses
        return redirect()->route('stok.index')->with('success', 'Produk berhasil dihapus!');
    }

public function edit($id)
{
    $stok = Stok::findOrFail($id);

    return view('layouts.edit', compact('stok'));
}
}