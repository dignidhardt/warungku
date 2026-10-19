<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Services\TransaksiService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class StokController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->get('cari', ''));

        $produk = Stok::query()
            ->when($keyword !== '', function ($query) use ($keyword) {
                // Dikelompokkan dalam satu closure: tanpa itu, orWhere milik
                // LIKE bisa lolos dari filter lain yang ditambahkan nanti.
                $query->where(function ($q) use ($keyword) {
                    $q->where('nama_produk', 'like', "%{$keyword}%")
                        ->orWhere('barcode', 'like', "%{$keyword}%")
                        ->orWhere('kategori', 'like', "%{$keyword}%");
                });
            })
            ->when($request->get('status') === 'menipis', fn ($q) => $q->whereColumn('stok', '<=', 'stok_minimal')->where('stok', '>', 0))
            ->when($request->get('status') === 'habis', fn ($q) => $q->where('stok', '<=', 0))
            ->orderBy('stok')
            ->orderBy('nama_produk')
            ->paginate(20)
            ->withQueryString();

        return view('stok.index', [
            'produk' => $produk,
            'keyword' => $keyword,
            'status' => $request->get('status'),
            'totalHabis' => Stok::where('stok', '<=', 0)->count(),
            'totalMenipis' => Stok::whereColumn('stok', '<=', 'stok_minimal')->where('stok', '>', 0)->count(),
        ]);
    }

    public function show(Stok $stok)
    {
        return view('stok.show', [
            'produk' => $stok,
            'mutasis' => $stok->mutasis()->with('user')->latest()->paginate(25),
        ]);
    }

    public function create()
    {
        return view('stok.create', [
            'produk' => new Stok(['stok_minimal' => 5, 'satuan' => 'pcs']),
            'daftarSatuan' => Stok::SATUAN_PILIHAN,
        ]);
    }

    public function store(Request $request, TransaksiService $service)
    {
        $data = $this->validasi($request);

        // Produk dibuat dengan stok 0, lalu stok awal dicatat sebagai mutasi.
        // `array_merge` dipakai (bukan `+`) karena operator `+` TIDAK menimpa
        // key yang sudah ada — `$data` sudah berisi 'stok' dari validasi, jadi
        // `$data + ['stok' => 0]` akan tetap memakai angka dari form dan
        // mutasi "stok awal" menghitung tambahan di atas angka itu (stok jadi
        // dobel).
        $produk = Stok::create(array_merge($data, ['stok' => 0]));

        // Stok awal dicatat sebagai mutasi, bukan langsung menimpa kolom,
        // supaya riwayat "kenapa stoknya segini" tetap ada.
        if ((int) $request->input('stok', 0) > 0) {
            $service->tambahStok($produk, (int) $request->input('stok'), 'Stok awal produk', $request->user());
        }

        return redirect()
            ->route('stok.index')
            ->with('success', "Produk '{$produk->nama_produk}' berhasil ditambahkan.");
    }

    public function edit(Stok $stok)
    {
        return view('stok.edit', [
            'produk' => $stok,
            'daftarSatuan' => Stok::SATUAN_PILIHAN,
        ]);
    }

    public function update(Request $request, Stok $stok)
    {
        $data = $this->validasi($request, $stok->id);

        // Kolom `stok` sengaja TIDAK ikut di-update di sini: perubahan stok
        // harus lewat penyesuaian agar tercatat di riwayat mutasi.
        unset($data['stok']);

        $stok->update($data);

        return redirect()
            ->route('stok.index')
            ->with('success', "Produk '{$stok->nama_produk}' berhasil diperbarui.");
    }

    public function destroy(Stok $stok)
    {
        $nama = $stok->nama_produk;
        $stok->delete();

        return redirect()
            ->route('stok.index')
            ->with('success', "Produk '{$nama}' berhasil dihapus.");
    }

    /**
     * Stok opname: menetapkan jumlah akhir beserta alasannya.
     */
    public function sesuaikan(Request $request, Stok $stok, TransaksiService $service)
    {
        $data = $request->validate([
            'stok_baru' => ['required', 'integer', 'min:0'],
            'keterangan' => ['required', 'string', 'max:255'],
        ]);

        try {
            $service->sesuaikanStok($stok, (int) $data['stok_baru'], $data['keterangan'], $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Stok '{$stok->nama_produk}' disesuaikan menjadi {$data['stok_baru']}.");
    }

    /**
     * Barang datang: menambah stok tanpa mengubah harga.
     *
     * `satuan` opsional: kalau produk punya kemasan (mis. karung 25 kg), kasir
     * boleh memasukkan "2" dengan satuan "karung" dan sistem mengubahnya jadi 50 kg.
     */
    public function tambah(Request $request, Stok $stok, TransaksiService $service)
    {
        $data = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1'],
            'satuan' => ['nullable', 'string', 'max:20'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        // Konversi dari satuan kemasan ke satuan dasar sebelum disimpan.
        $jumlah = $stok->keSatuanDasar((int) $data['jumlah'], $data['satuan'] ?? null);

        try {
            $service->tambahStok(
                $stok,
                $jumlah,
                $data['keterangan'] ?: 'Penambahan stok',
                $request->user()
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Stok '{$stok->nama_produk}' ditambah ".$stok->labelStok($jumlah).'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?int $idProduk = null): array
    {
        return $request->validate([
            'barcode' => [
                'required', 'string', 'max:64',
                // Barcode unik: dua produk berkode sama membuat pemindaian di
                // kasir memilih barang yang salah.
                Rule::unique('stok', 'barcode')->ignore($idProduk),
            ],
            'nama_produk' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:20'],
            'satuan_kemasan' => ['nullable', 'string', 'max:20', 'required_with:isi_kemasan'],
            'isi_kemasan' => ['nullable', 'integer', 'min:1', 'required_with:satuan_kemasan'],
            'kategori' => ['required', 'string', 'max:100'],
            'harga_beli' => ['required', 'integer', 'min:0'],
            'harga_jual' => ['required', 'integer', 'min:0', 'gte:harga_beli'],
            'stok' => ['nullable', 'integer', 'min:0'],
            'stok_minimal' => ['nullable', 'integer', 'min:0'],
        ], [
            'harga_jual.gte' => 'Harga jual tidak boleh lebih rendah dari harga beli.',
            'barcode.unique' => 'Barcode ini sudah dipakai produk lain.',
            'satuan.required' => 'Satuan wajib diisi (pcs, kg, liter, dll).',
            'satuan_kemasan.required_with' => 'Isi kemasan wajib diisi kalau nama kemasan diisi.',
            'isi_kemasan.required_with' => 'Nama kemasan wajib diisi kalau isi kemasan diisi.',
        ]);
    }
}
