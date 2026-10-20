<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Satuan;
use App\Models\SatuanKemasan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Master data sederhana: kategori, satuan, satuan kemasan.
 *
 * Tiga tabel ini struktur dan perawatannya sama persis (nama unik,
 * tambah/ubah/hapus), jadi satu controller suffice. Yang membedakan
 * hanya cara kode barangnya dibuat:
 *
 * - Kategori punya "Kode Barang" 3 karakter (mis. "AAA") yang jadi
 *   prefiks Kode Barang produk ("AAA-001").
 * - Satuan dan Satuan Kemasan tidak punya kode; identitasnya namanya.
 *
 * Kenapa halaman-halaman ini perlu ada: sebelumnya opsi kategori dan
 * satuan ditulis bebas sebagai teks di form produk. Satu produk bisa
 * punya kategori "Makanan" dan produk lain "makanan " (ada spasi),
 * sehingga laporan per-kategori terpecah dan filter tidak akurat.
 */
class MasterDataController extends Controller
{
    public function kategori(Request $request)
    {
        $daftar = Kategori::withCount('produk')
            ->orderBy('nama')
            ->paginate(25)
            ->withQueryString();

        return view('master.kategori', [
            'daftar' => $daftar,
            'cari' => trim((string) $request->get('cari', '')),
        ]);
    }

    public function simpanKategori(Request $request)
    {
        $data = $request->validate([
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('kategoris', 'nama'),
            ],
            'kode_barang' => [
                'nullable', 'string', 'min:3', 'max:3',
                'regex:/^[A-Za-z0-9]{3}$/',
                Rule::unique('kategoris', 'kode_barang'),
            ],
        ], [
            'kode_barang.regex' => 'Kode Barang harus 3 karakter (angka dan huruf), mis. AAA.',
            'kode_barang.min' => 'Kode Barang harus 3 karakter.',
            'kode_barang.max' => 'Kode Barang harus 3 karakter.',
            'nama.unique' => 'Kategori dengan nama ini sudah ada.',
            'kode_barang.unique' => 'Kode Barang ini sudah dipakai kategori lain.',
        ]);

        $kode = strtoupper(trim((string) ($data['kode_barang'] ?? '')));

        // Kode Barang kosong -> buat otomatis dari nama (3 huruf pertama).
        if ($kode === '') {
            $kode = $this->kodeKategoriOtomatis($data['nama']);
        }

        Kategori::create([
            'nama' => trim($data['nama']),
            'kode_barang' => $kode,
        ]);

        return redirect()
            ->route('master.kategori')
            ->with('success', "Kategori '{$data['nama']}' berhasil ditambahkan.");
    }

    public function ubahKategori(Request $request, Kategori $kategori)
    {
        $data = $request->validate([
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('kategoris', 'nama')->ignore($kategori->id),
            ],
            'kode_barang' => [
                'nullable', 'string', 'min:3', 'max:3',
                'regex:/^[A-Za-z0-9]{3}$/',
                Rule::unique('kategoris', 'kode_barang')->ignore($kategori->id),
            ],
        ], [
            'kode_barang.regex' => 'Kode Barang harus 3 karakter (angka dan huruf), mis. AAA.',
            'nama.unique' => 'Kategori dengan nama ini sudah ada.',
            'kode_barang.unique' => 'Kode Barang ini sudah dipakai kategori lain.',
        ]);

        $kode = strtoupper(trim((string) ($data['kode_barang'] ?? '')));

        $kategori->update([
            'nama' => trim($data['nama']),
            'kode_barang' => $kode !== '' ? $kode : $kategori->kode_barang,
        ]);

        return redirect()
            ->route('master.kategori')
            ->with('success', "Kategori '{$kategori->nama}' berhasil diperbarui.");
    }

    public function hapusKategori(Kategori $kategori)
    {
        $nama = $kategori->nama;
        $jumlahProduk = $kategori->produk()->count();

        // Produk tidak ikut terhapus: foreign key-nya nullOnDelete, jadi
        // produk tetap ada tapi kategorinya kosong. Hapus kategori tidak
        // boleh membuat produk hilang.
        $kategori->delete();

        $catatan = $jumlahProduk > 0
            ? " {$jumlahProduk} produk yang memakainya jadi tanpa kategori."
            : '';

        return redirect()
            ->route('master.kategori')
            ->with('success', "Kategori '{$nama}' berhasil dihapus.{$catatan}");
    }

    public function satuan(Request $request)
    {
        $daftar = Satuan::orderBy('nama')->paginate(25)->withQueryString();

        return view('master.satuan', [
            'daftar' => $daftar,
            'cari' => trim((string) $request->get('cari', '')),
        ]);
    }

    public function simpanSatuan(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:50', Rule::unique('satuans', 'nama')],
        ], [
            'nama.unique' => 'Satuan ini sudah ada.',
        ]);

        Satuan::create(['nama' => trim($data['nama'])]);

        return redirect()
            ->route('master.satuan')
            ->with('success', "Satuan '{$data['nama']}' berhasil ditambahkan.");
    }

    public function ubahSatuan(Request $request, Satuan $satuan)
    {
        $data = $request->validate([
            'nama' => [
                'required', 'string', 'max:50',
                Rule::unique('satuans', 'nama')->ignore($satuan->id),
            ],
        ], [
            'nama.unique' => 'Satuan ini sudah ada.',
        ]);

        $satuan->update(['nama' => trim($data['nama'])]);

        return redirect()
            ->route('master.satuan')
            ->with('success', "Satuan '{$satuan->nama}' berhasil diperbarui.");
    }

    public function hapusSatuan(Satuan $satuan)
    {
        $nama = $satuan->nama;

        // Produk yang memakainya tidak ikut terhapus; kolom satuan di
        // produk tetap berisi nama satuan lamanya (produk masih punya
        // satuan yang valid).
        $satuan->delete();

        return redirect()
            ->route('master.satuan')
            ->with('success', "Satuan '{$nama}' berhasil dihapus.");
    }

    public function satuanKemasan(Request $request)
    {
        $daftar = SatuanKemasan::orderBy('nama')->paginate(25)->withQueryString();

        return view('master.satuan-kemasan', [
            'daftar' => $daftar,
            'cari' => trim((string) $request->get('cari', '')),
        ]);
    }

    public function simpanSatuanKemasan(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:50', Rule::unique('satuan_kemisans', 'nama')],
        ], [
            'nama.unique' => 'Satuan kemasan ini sudah ada.',
        ]);

        SatuanKemasan::create(['nama' => trim($data['nama'])]);

        return redirect()
            ->route('master.satuan-kemasan')
            ->with('success', "Satuan kemasan '{$data['nama']}' berhasil ditambahkan.");
    }

    public function ubahSatuanKemasan(Request $request, SatuanKemasan $satuanKemasan)
    {
        $data = $request->validate([
            'nama' => [
                'required', 'string', 'max:50',
                Rule::unique('satuan_kemisans', 'nama')->ignore($satuanKemasan->id),
            ],
        ], [
            'nama.unique' => 'Satuan kemasan ini sudah ada.',
        ]);

        $satuanKemasan->update(['nama' => trim($data['nama'])]);

        return redirect()
            ->route('master.satuan-kemasan')
            ->with('success', "Satuan kemasan '{$satuanKemasan->nama}' berhasil diperbarui.");
    }

    public function hapusSatuanKemasan(SatuanKemasan $satuanKemasan)
    {
        $nama = $satuanKemasan->nama;
        $satuanKemasan->delete();

        return redirect()
            ->route('master.satuan-kemasan')
            ->with('success', "Satuan kemasan '{$nama}' berhasil dihapus.");
    }

    /**
     * Kode Barang 3 karakter dari nama kategori.
     *
     * Coba 3 huruf/angka pertama dari nama. Kalau tidak menghasilkan 3
     * karakter atau sudah dipakai, lanjut ke urutan AAA, AAB, AAC, ...
     */
    private function kodeKategoriOtomatis(string $nama): string
    {
        $huruf = Str::upper(Str::of($nama)->replaceMatches('/[^A-Za-z0-9]/', '')->substr(0, 3));

        if (strlen($huruf) === 3 && ! Kategori::where('kode_barang', $huruf)->exists()) {
            return $huruf;
        }

        return $this->kodeKategoriBerikutnya();
    }

    private function kodeKategoriBerikutnya(): string
    {
        $huruf = range('A', 'Z');

        for ($a = 0; $a < 26; $a++) {
            for ($b = 0; $b < 26; $b++) {
                for ($c = 0; $c < 26; $c++) {
                    $kode = $huruf[$a].$huruf[$b].$huruf[$c];

                    if (! Kategori::where('kode_barang', $kode)->exists()) {
                        return $kode;
                    }
                }
            }
        }

        return 'ZZ'.random_int(0, 9);
    }
}
