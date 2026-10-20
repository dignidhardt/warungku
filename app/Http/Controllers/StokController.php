<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Satuan;
use App\Models\SatuanKemasan;
use App\Models\Stok;
use App\Models\StokMutasi;
use App\Models\Supplier;
use App\Services\TransaksiService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class StokController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->get('cari', ''));
        $status = (string) $request->get('status', '');

        // Parameter filter yang ikut dibawa ke link paginasi. Yang kosong
        // sengaja dibuang supaya URL tidak penuh "cari=&status=".
        $filter = array_filter([
            'cari' => $keyword,
            'status' => $status,
        ]);

        $produk = Stok::query()
            ->with(['kategori', 'supplier'])
            ->when($keyword !== '', function ($query) use ($keyword) {
                // Dikelompokkan dalam satu closure: tanpa itu, orWhere milik
                // LIKE bisa lolos dari filter lain yang ditambahkan nanti.
                $query->where(function ($q) use ($keyword) {
                    $q->where('nama_produk', 'like', "%{$keyword}%")
                        ->orWhere('barcode', 'like', "%{$keyword}%")
                        ->orWhere('kategori', 'like', "%{$keyword}%");
                });
            })
            ->when($status === 'menipis', fn ($q) => $q->whereColumn('stok', '<=', 'stok_minimal')->where('stok', '>', 0))
            ->when($status === 'habis', fn ($q) => $q->where('stok', '<=', 0))
            ->orderBy('stok')
            ->orderBy('nama_produk')
            ->paginate(20, ['*'], 'pageProduk')
            ->appends($filter);

        // Riwayat stok: seluruh mutasi masuk dan keluar, terbaru lebih dulu.
        // Paginasi dipisah dari daftar produk (pageProduk / pageMutasi)
        // supaya menggulir riwayat tidak menggeser halaman daftar produk.
        $mutasis = StokMutasi::query()
            ->with(['produk', 'user'])
            ->latest('id')
            ->paginate(25, ['*'], 'pageMutasi')
            ->appends($filter);

        return view('stok.index', [
            'produk' => $produk,
            'mutasis' => $mutasis,
            'keyword' => $keyword,
            'status' => $status,
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

    public function create(Request $request)
    {
        $kategoriTerpilih = null;

        // Kalau datang dari halaman kategori ("tambah produk di kategori
        // ini"), kategori langsung terisi.
        if ($request->filled('kategori_id')) {
            $kategoriTerpilih = Kategori::find($request->integer('kategori_id'));
        }

        $daftarKategori = Kategori::orderBy('nama')->get();

        return view('stok.create', [
            'produk' => new Stok(['stok_minimal' => 5]),
            'kategoriTerpilih' => $kategoriTerpilih,
            'kodeBarangOtomatis' => Stok::kodeBarangOtomatis($kategoriTerpilih),
            'daftarKategori' => $daftarKategori,
            'petaKodeBarang' => $this->petaKodeBarang($daftarKategori),
            'daftarSatuan' => Satuan::orderBy('nama')->pluck('nama'),
            'daftarSatuanKemasan' => SatuanKemasan::orderBy('nama')->pluck('nama'),
            'daftarSupplier' => Supplier::orderBy('nama')->get(),
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
        $daftarKategori = Kategori::orderBy('nama')->get();

        return view('stok.edit', [
            'produk' => $stok,
            'kategoriTerpilih' => $stok->kategori,
            'kodeBarangOtomatis' => Stok::kodeBarangOtomatis($stok->kategori),
            'daftarKategori' => $daftarKategori,
            'petaKodeBarang' => $this->petaKodeBarang($daftarKategori),
            'daftarSatuan' => Satuan::orderBy('nama')->pluck('nama'),
            'daftarSatuanKemasan' => SatuanKemasan::orderBy('nama')->pluck('nama'),
            'daftarSupplier' => Supplier::orderBy('nama')->get(),
        ]);
    }

    /**
     * Peta "kategori id -> Kode Barang berikutnya".
     *
     * Dikirim ke browser supaya pilihan kategori langsung menghasilkan kode
     * yang benar tanpa perlu reload halaman.
     *
     * @param  \Illuminate\Support\Collection<int, Kategori>  $daftarKategori
     * @return array<int, string>
     */
    private function petaKodeBarang($daftarKategori): array
    {
        $peta = [];

        foreach ($daftarKategori as $kategori) {
            $peta[$kategori->id] = Stok::kodeBarangOtomatis($kategori);
        }

        return $peta;
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
        $data = $request->validate([
            'barcode' => [
                'required', 'string', 'max:64',
                // Barcode unik: dua produk berkode sama membuat pemindaian di
                // kasir memilih barang yang salah.
                Rule::unique('stok', 'barcode')->ignore($idProduk),
            ],
            'nama_produk' => ['required', 'string', 'max:255'],
            'satuan' => [
                'required', 'string', 'max:20',
                // Satuan harus salah satu opsi yang terdaftar di master data.
                Rule::exists('satuans', 'nama'),
            ],
            'satuan_kemasan' => [
                'nullable', 'string', 'max:20',
                Rule::exists('satuan_kemisans', 'nama'),
                'required_with:isi_kemasan',
            ],
            'isi_kemasan' => ['nullable', 'integer', 'min:1', 'required_with:satuan_kemasan'],
            'kategori_id' => ['required', 'integer', 'exists:kategoris,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'harga_beli' => ['required', 'integer', 'min:0'],
            'harga_jual' => ['required', 'integer', 'min:0', 'gte:harga_beli'],
            'stok' => ['nullable', 'integer', 'min:0'],
            'stok_minimal' => ['nullable', 'integer', 'min:0'],
        ], [
            'harga_jual.gte' => 'Harga jual tidak boleh lebih rendah dari harga beli.',
            'barcode.unique' => 'Kode Barang ini sudah dipakai produk lain.',
            'satuan.required' => 'Satuan wajib diisi (pcs, kg, liter, dll).',
            'satuan.exists' => 'Satuan ini belum terdaftar di master data Satuan.',
            'satuan_kemasan.exists' => 'Satuan kemasan ini belum terdaftar di master data Satuan Kemasan.',
            'satuan_kemasan.required_with' => 'Isi kemasan wajib diisi kalau nama kemasan diisi.',
            'isi_kemasan.required_with' => 'Nama kemasan wajib diisi kalau isi kemasan diisi.',
            'kategori_id.required' => 'Kategori wajib dipilih.',
            'kategori_id.exists' => 'Kategori ini tidak ditemukan.',
            'supplier_id.required' => 'Supplier wajib dipilih.',
            'supplier_id.exists' => 'Supplier ini tidak ditemukan.',
        ]);

        // Kolom teks `kategori` disimpan sebagai salinan tampilan supaya
        // laporan per-kategori dan pencarian lama tetap bekerja.
        $data['kategori'] = Kategori::find($data['kategori_id'])?->nama;

        return $data;
    }
}
