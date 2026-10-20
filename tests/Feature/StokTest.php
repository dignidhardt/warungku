<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Stok;
use App\Models\StokMutasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuatMasterData;
use Tests\TestCase;

class StokTest extends TestCase
{
    use BuatMasterData;
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function produk(array $ubah = []): Stok
    {
        // Produk sekarang butuh kategori & supplier dari master data: form
        // produk memakai dropdown, bukan teks bebas.
        $kategori = $this->buatKategori('Minuman');
        $supplier = $this->buatSupplier('Toko Besar');
        $this->buatSatuan('pcs');

        return Stok::create(array_merge([
            'barcode' => 'BRG-001',
            'nama_produk' => 'Kopi Sachet',
            'satuan' => 'pcs',
            'kategori' => 'Minuman',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 2000,
            'harga_jual' => 3000,
            'stok' => 10,
            'stok_minimal' => 5,
        ], $ubah));
    }

    public function test_daftar_stok_bisa_dibuka_kasir(): void
    {
        $this->produk();
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->actingAs($kasir)
            ->get(route('stok.index'))
            ->assertOk()
            ->assertSee('Kopi Sachet');
    }

    public function test_admin_bisa_menambah_produk_dan_stok_awal_tercatat(): void
    {
        $admin = $this->admin();

        $kategori = $this->buatKategori('Minuman');
        $supplier = $this->buatSupplier('Toko Besar');
        $this->buatSatuan('pcs');

        $this->actingAs($admin)->post(route('stok.store'), [
            'barcode' => 'BRG-999',
            'nama_produk' => 'Teh Botol',
            'satuan' => 'pcs',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 3000,
            'harga_jual' => 5000,
            'stok' => 7,
            'stok_minimal' => 3,
        ])->assertRedirect(route('stok.index'));

        $produk = Stok::where('barcode', 'BRG-999')->firstOrFail();

        $this->assertSame(7, $produk->stok);

        // Stok awal harus jadi mutasi, bukan angka tanpa jejak.
        $this->assertDatabaseHas('stok_mutasis', [
            'stok_id' => $produk->id,
            'jenis' => StokMutasi::JENIS_MASUK,
            'jumlah' => 7,
            'stok_sesudah' => 7,
        ]);
    }

    public function test_barcode_ganda_ditolak(): void
    {
        $this->produk(['barcode' => 'SAMA-1']);

        $kategori = $this->buatKategori('Snack');
        $supplier = $this->buatSupplier('Toko Besar');

        $this->actingAs($this->admin())->post(route('stok.store'), [
            'barcode' => 'SAMA-1',
            'satuan' => 'pcs',
            'nama_produk' => 'Produk Lain',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 1,
        ])->assertSessionHasErrors('barcode');
    }

    public function test_harga_jual_di_bawah_harga_beli_ditolak(): void
    {
        $kategori = $this->buatKategori('Snack');
        $supplier = $this->buatSupplier('Toko Besar');

        $this->actingAs($this->admin())->post(route('stok.store'), [
            'barcode' => 'BRG-002',
            'satuan' => 'pcs',
            'nama_produk' => 'Rugi',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 5000,
            'harga_jual' => 4000,
            'stok' => 1,
        ])->assertSessionHasErrors('harga_jual');
    }

    public function test_kasir_tidak_bisa_menambah_produk(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $kategori = $this->buatKategori('Snack');
        $supplier = $this->buatSupplier('Toko Besar');

        $this->actingAs($kasir)->post(route('stok.store'), [
            'barcode' => 'BRG-003',
            'satuan' => 'pcs',
            'nama_produk' => 'Tidak Boleh',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 1,
        ])->assertRedirect(route('home'));

        $this->assertDatabaseMissing('stok', ['barcode' => 'BRG-003']);
    }

    public function test_edit_produk_tidak_boleh_mengubah_stok_langsung(): void
    {
        $produk = $this->produk(['stok' => 10]);

        $this->actingAs($this->admin())->put(route('stok.update', $produk), [
            'barcode' => $produk->barcode,
            'nama_produk' => 'Kopi Sachet Baru',
            'satuan' => 'pcs',
            'kategori_id' => $produk->kategori_id,
            'supplier_id' => $produk->supplier_id,
            'harga_beli' => 2000,
            'harga_jual' => 3500,
            'stok' => 999,
            'stok_minimal' => 5,
        ])->assertRedirect(route('stok.index'));

        $produk->refresh();

        $this->assertSame('Kopi Sachet Baru', $produk->nama_produk);
        $this->assertSame(10, $produk->stok, 'Stok tidak boleh berubah lewat form produk.');
    }

    public function test_penyesuaian_stok_mencatat_alasan_dan_selisih(): void
    {
        $produk = $this->produk(['stok' => 10]);

        $this->actingAs($this->admin())->post(route('stok.sesuaikan', $produk), [
            'stok_baru' => 6,
            'keterangan' => 'Rusak 4 pcs',
        ])->assertRedirect();

        $this->assertSame(6, $produk->refresh()->stok);

        $this->assertDatabaseHas('stok_mutasis', [
            'stok_id' => $produk->id,
            'jenis' => StokMutasi::JENIS_PENYESUAIAN,
            'jumlah' => 4,
            'stok_sesudah' => 6,
            'keterangan' => 'Rusak 4 pcs',
        ]);
    }

    public function test_penambahan_stok_menambah_dan_mencatat_mutasi(): void
    {
        $produk = $this->produk(['stok' => 5]);

        $this->actingAs($this->admin())->post(route('stok.tambah', $produk), [
            'jumlah' => 20,
            'keterangan' => 'Kiriman supplier',
        ])->assertRedirect();

        $this->assertSame(25, $produk->refresh()->stok);

        $this->assertDatabaseHas('stok_mutasis', [
            'stok_id' => $produk->id,
            'jenis' => StokMutasi::JENIS_MASUK,
            'jumlah' => 20,
            'stok_sesudah' => 25,
        ]);
    }

    public function test_status_stok_menipis_dan_habis_dihitung_benar(): void
    {
        $this->produk(['barcode' => 'A', 'stok' => 10, 'stok_minimal' => 5]);
        $this->produk(['barcode' => 'B', 'stok' => 3, 'stok_minimal' => 5]);
        $this->produk(['barcode' => 'C', 'stok' => 0, 'stok_minimal' => 5]);

        $this->assertSame('aman', Stok::where('barcode', 'A')->first()->status_stok);
        $this->assertSame('menipis', Stok::where('barcode', 'B')->first()->status_stok);
        $this->assertSame('habis', Stok::where('barcode', 'C')->first()->status_stok);
    }

    public function test_pencarian_produk_menyaring_hasil(): void
    {
        $this->produk(['barcode' => 'X1', 'nama_produk' => 'Kopi Sachet']);
        $this->produk(['barcode' => 'X2', 'nama_produk' => 'Sabun Mandi']);

        $this->actingAs($this->admin())
            ->get(route('stok.index', ['cari' => 'Kopi']))
            ->assertOk()
            ->assertSee('Kopi Sachet')
            ->assertDontSee('Sabun Mandi');
    }

    public function test_halaman_stok_menampilkan_riwayat_stok_masuk_dan_keluar(): void
    {
        $produk = $this->produk(['stok' => 10]);

        StokMutasi::create([
            'stok_id' => $produk->id,
            'jenis' => StokMutasi::JENIS_MASUK,
            'jumlah' => 10,
            'stok_sesudah' => 10,
            'keterangan' => 'Stok awal produk',
        ]);

        StokMutasi::create([
            'stok_id' => $produk->id,
            'jenis' => StokMutasi::JENIS_KELUAR,
            'jumlah' => 3,
            'stok_sesudah' => 7,
            'keterangan' => 'Penjualan TRX-1',
        ]);

        $html = $this->actingAs($this->admin())->get(route('stok.index'))->assertOk()->getContent();

        // Dua container: "Stok" (daftar produk) dan "Riwayat Stok" (mutasi).
        $this->assertStringContainsString('>Stok<', $html);
        $this->assertStringContainsString('>Riwayat Stok<', $html);

        // Kedua jenis mutasi harus muncul di riwayat.
        $this->assertStringContainsString('Masuk', $html);
        $this->assertStringContainsString('Keluar', $html);
        $this->assertStringContainsString('Penjualan TRX-1', $html);
    }

    public function test_riwayat_stok_kosong_tetap_merender_tanpa_error(): void
    {
        $this->produk();

        $this->actingAs($this->admin())
            ->get(route('stok.index'))
            ->assertOk()
            ->assertSee('Riwayat Stok');
    }

    public function test_paginasi_produk_dan_riwayat_stok_terpisah(): void
    {
        // Dua tabel di satu halaman harus punya parameter paginasi sendiri.
        // Kalau tidak, menggulir riwayat stok akan menggeser halaman daftar
        // produk.
        $this->produk();

        $response = $this->actingAs($this->admin())->get(route('stok.index'))->assertOk();

        $response->assertViewHas('produk', function ($produk) {
            return $produk->getPageName() === 'pageProduk';
        });

        $response->assertViewHas('mutasis', function ($mutasis) {
            return $mutasis->getPageName() === 'pageMutasi';
        });
    }

    public function test_filter_produk_tetap_berlaku_saat_riwayat_stok_dibuka(): void
    {
        $apel = $this->produk(['barcode' => 'F-1', 'nama_produk' => 'Apel']);
        $sabon = $this->produk(['barcode' => 'F-2', 'nama_produk' => 'Sabun']);

        $this->actingAs($this->admin())
            ->get(route('stok.index', ['cari' => 'Apel']))
            ->assertOk()
            ->assertSee('Apel')
            ->assertDontSee('Sabun');
    }

    public function test_kategori_dan_supplier_wajib_diisi(): void
    {
        // Form produk memakai dropdown dari master data: kategori dan
        // supplier wajib dipilih, tidak boleh diisi teks bebas.
        $this->buatSatuan('pcs');

        $this->actingAs($this->admin())->post(route('stok.store'), [
            'barcode' => 'WAJIB-1',
            'nama_produk' => 'Tanpa Kategori',
            'satuan' => 'pcs',
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 1,
        ])->assertSessionHasErrors(['kategori_id', 'supplier_id']);
    }

    public function test_satuan_harus_terdaftar_di_master_data(): void
    {
        // Satuan yang diketik bebas (mis. "kg") ditolak kalau tidak ada
        // di master data Satuan.
        $kategori = $this->buatKategori('Minuman');
        $supplier = $this->buatSupplier('Toko Besar');

        $this->actingAs($this->admin())->post(route('stok.store'), [
            'barcode' => 'SAT-1',
            'nama_produk' => 'Satuan Aneh',
            'satuan' => 'gallon',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 1,
        ])->assertSessionHasErrors('satuan');
    }

    public function test_kode_barang_otomatis_dari_kategori(): void
    {
        // Kategori "AAA" -> produk pertama di kategori itu dapat "AAA-001",
        // produk kedua "AAA-002".
        $kategori = Kategori::create(['nama' => 'Minuman Set', 'kode_barang' => 'AAA']);
        $supplier = $this->buatSupplier('Toko Besar');
        $this->buatSatuan('pcs');

        $this->assertSame('AAA-001', Stok::kodeBarangOtomatis($kategori));

        $this->actingAs($this->admin())->post(route('stok.store'), [
            'barcode' => 'AAA-001',
            'nama_produk' => 'Produk Pertama',
            'satuan' => 'pcs',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 1,
        ])->assertRedirect(route('stok.index'));

        $this->assertSame('AAA-002', Stok::kodeBarangOtomatis($kategori));
    }

    public function test_halaman_tambah_produk_menampilkan_dropdown_dari_master_data(): void
    {
        $kategori = $this->buatKategori('Minuman');
        $supplier = $this->buatSupplier('Toko Besar');
        $this->buatSatuan('pcs');

        $html = $this->actingAs($this->admin())->get(route('stok.create'))->assertOk()->getContent();

        // Opsi kategori & supplier harus ada di dalam <select>.
        $this->assertStringContainsString('value="'.$kategori->id.'"', $html);
        $this->assertStringContainsString('value="'.$supplier->id.'"', $html);
        $this->assertStringContainsString('Minuman ('.$kategori->kode_barang, $html);
        $this->assertStringContainsString('Toko Besar ('.$supplier->kode, $html);

        // Input teks kustom tidak boleh ada: kategori & satuan wajib lewat
        // dropdown.
        $this->assertStringNotContainsString('name="kategori"', $html);
        $this->assertStringNotContainsString('list="daftar-kategori"', $html);
    }
}
