<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi; 
use App\Models\Stok; 

class TransaksiController extends Controller
{
    // Menampilkan halaman utama transaksi (Form Kasir / Penjualan)
    public function index()
    {
        // Mengambil semua data stok produk untuk pilihan di form transaksi
        $produk = Stok::where('stok', '>', 0)->get(); 
        
        return view('layouts.transaksi', compact('produk'));
    }

    // Memproses penyimpanan data transaksi ke database & laporan
    public function store(Request $request)
    {
        // 1. Validasi format data awal yang masuk
        $request->validate([
            'keranjang' => 'required|json',
            'grand_total' => 'required|numeric',
            'bayar' => 'required|numeric',
            'kembali' => 'required|numeric',
        ]);

        $items = json_decode($request->keranjang, true);

        // 2. TAHAP VALIDASI KRUSIAL: Cek kecukupan stok semua barang di keranjang terlebih dahulu
        foreach ($items as $item) {
            $produk = Stok::find($item['id']);
            
            // Jika produk tidak ditemukan atau stok di database kurang dari yang mau dibeli
            if (!$produk || $produk->stok < $item['qty']) {
                $namaProduk = $produk ? $produk->nama_produk : 'Produk tidak dikenal';
                $sisaStok = $produk ? $produk->stok : 0;

                // Batalkan proses dan kirim respon error ke JavaScript kasir
                return response()->json([
                    'success' => false, 
                    'message' => "Transaksi Gagal! Stok untuk '{$namaProduk}' tidak mencukupi. Sisa stok saat ini: {$sisaStok} pcs."
                ], 422); // Status 422 Unprocessable Entity
            }
        }

        // 3. TAHAP EKSEKUSI: Jika semua barang lolos validasi, baru potong stoknya
        foreach ($items as $item) {
            $produk = Stok::find($item['id']);
            if ($produk) {
                $produk->stok = $produk->stok - $item['qty'];
                $produk->save();
            }
        }

        // 4. Simpan data ke tabel transaksi untuk laporan
        Transaksi::create([
            'nomor_transaksi' => 'TRX-' . time(),
            'detail_produk' => $request->keranjang,
            'grand_total' => $request->grand_total,
            'bayar' => $request->bayar,
            'kembali' => $request->kembali,
        ]);

        return response()->json(['success' => true, 'message' => 'Transaksi Berhasil Disimpan!']);
    }
}