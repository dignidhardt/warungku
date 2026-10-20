<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Supplier barang dagangan.
 *
 * Setiap produk punya satu supplier. Supplier inilah yang dipakai otomatis
 * di form Pembelian: kasir memilih barang, sistem mengambil supplier dari
 * produk itu. Jadi admin cukup，维护 daftar supplier sekali, bukan mengetik
 * ulang nama supplier tiap kali mencatat pembelian.
 */
class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->get('cari', ''));

        $daftar = Supplier::withCount('produk')
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where(function ($q) use ($cari) {
                    $q->where('nama', 'like', "%{$cari}%")
                        ->orWhere('kode', 'like', "%{$cari}%")
                        ->orWhere('telepon', 'like', "%{$cari}%");
                });
            })
            ->orderBy('nama')
            ->paginate(25)
            ->withQueryString();

        return view('master.supplier', [
            'daftar' => $daftar,
            'cari' => $cari,
        ]);
    }

    public function create()
    {
        return view('master.supplier-form', [
            'supplier' => new Supplier(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        $supplier = Supplier::create([
            'nama' => huruf_awal_besar($data['nama']),
            // Kode supplier otomatis (SUP-001, SUP-002, dst) supaya admin
            // tidak perlu menghafal urutannya.
            'kode' => Supplier::kodeBerikutnya(),
            'telepon' => $data['telepon'] ?? null,
            'alamat' => $data['alamat'] ?? null,
        ]);

        return redirect()
            ->route('master.supplier')
            ->with('success', "Supplier '{$supplier->nama}' ({$supplier->kode}) berhasil ditambahkan.");
    }

    public function edit(Supplier $supplier)
    {
        return view('master.supplier-form', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $this->validasi($request, $supplier->id);

        $supplier->update([
            'nama' => huruf_awal_besar($data['nama']),
            'telepon' => $data['telepon'] ?? null,
            'alamat' => $data['alamat'] ?? null,
        ]);

        return redirect()
            ->route('master.supplier')
            ->with('success', "Supplier '{$supplier->nama}' berhasil diperbarui.");
    }

    public function destroy(Supplier $supplier)
    {
        $nama = $supplier->nama;

        // Produk yang memakai supplier ini TIDAK ikut terhapus. Foreign
        // key-nya nullOnDelete, jadi produk tetap ada tapi suppliernya
        // kosong — lebih baik daripada produk ikut hilang.
        $jumlahProduk = $supplier->produk()->count();

        $supplier->delete();

        $catatan = $jumlahProduk > 0
            ? " {$jumlahProduk} produk yang memakainya jadi tanpa supplier."
            : '';

        return redirect()
            ->route('master.supplier')
            ->with('success', "Supplier '{$nama}' berhasil dihapus.{$catatan}");
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?int $idSupplier = null): array
    {
        return $request->validate([
            'nama' => [
                'required', 'string', 'max:120',
                Rule::unique('suppliers', 'nama')->ignore($idSupplier),
            ],
            'telepon' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:255'],
        ], [
            'nama.unique' => 'Supplier dengan nama ini sudah ada.',
        ]);
    }
}
