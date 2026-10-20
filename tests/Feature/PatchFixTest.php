<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Satuan;
use App\Models\SatuanKemasan;
use App\Models\Stok;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuatMasterData;
use Tests\TestCase;

/**
 * Test untuk patchfix:latest:
 * - Format rupiah seragam ("Rp 100.000,00").
 * - Master data memakai huruf awal besar.
 * - Hak akses "Gudang".
 * - Halaman detail produk hanya read-only.
 */
class PatchFixTest extends TestCase
{
    use BuatMasterData;
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function gudang(): User
    {
        return User::factory()->create(['role' => User::ROLE_GUDANG]);
    }

    private function kasir(): User
    {
        return User::factory()->create(['role' => User::ROLE_KASIR]);
    }

    /* ---------------- Format rupiah ---------------- */

    public function test_format_rupiah_pakai_desimal_selalu(): void
    {
        $this->assertSame('Rp 100.000,00', uang(100000));
        $this->assertSame('Rp 0,00', uang(0));
        $this->assertSame('Rp 1.234,56', uang(1234.56));
        $this->assertSame('Rp 1.250,00', uang(1250));
    }

    public function test_format_rupiah_negatif_pakai_tanda_minus(): void
    {
        $this->assertSame('− Rp 5.000,00', uang(-5000));
    }

    public function test_format_rupiah_konsisten_di_semua_halaman(): void
    {
        $admin = $this->admin();

        $kategori = $this->buatKategori('Minuman');
        $supplier = $this->buatSupplier('Toko Besar');
        $this->buatSatuan('pcs');

        Stok::create([
            'barcode' => 'BRG-1',
            'nama_produk' => 'Kopi',
            'satuan' => 'pcs',
            'kategori' => 'Minuman',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 12500,
            'harga_jual' => 15000,
            'stok' => 10,
            'stok_minimal' => 2,
        ]);

        foreach (['transaksi.index', 'transaksi.riwayat', 'stok.index', 'laporan.index'] as $rute) {
            $html = $this->actingAs($admin)->get(route($rute))->assertOk()->getContent();

            // Tidak boleh ada format lama tanpa desimal.
            $this->assertDoesNotMatchRegularExpression(
                '/Rp\s?[0-9.]+(?![\d.,])/',
                str_replace('Rp 0,00', '', $html),
                "Halaman $rute masih memakai format rupiah lama."
            );
        }
    }

    public function test_harga_produk_tampil_dengan_desimal(): void
    {
        $admin = $this->admin();

        $kategori = $this->buatKategori('Minuman');
        $supplier = $this->buatSupplier('Toko Besar');
        $this->buatSatuan('pcs');

        Stok::create([
            'barcode' => 'BRG-1',
            'nama_produk' => 'Kopi',
            'satuan' => 'pcs',
            'kategori' => 'Minuman',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 12500,
            'harga_jual' => 15000,
            'stok' => 10,
            'stok_minimal' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('stok.index'))
            ->assertOk()
            ->assertSee('Rp 12.500,00', false)
            ->assertSee('Rp 15.000,00', false);
    }

    /* ---------------- Huruf awal besar ---------------- */

    public function test_nama_master_data_dibuat_dengan_huruf_awal_besar(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('master.kategori.simpan'), [
            'nama' => 'makanan ringan',
        ])->assertRedirect(route('master.kategori'));

        $this->actingAs($admin)->post(route('master.satuan.simpan'), [
            'nama' => 'botol',
        ])->assertRedirect(route('master.satuan'));

        $this->actingAs($admin)->post(route('master.satuan-kemasan.simpan'), [
            'nama' => 'dus',
        ])->assertRedirect(route('master.satuan-kemasan'));

        $this->actingAs($admin)->post(route('master.supplier.store'), [
            'nama' => 'toko adi',
        ])->assertRedirect(route('master.supplier'));

        $this->assertSame('Makanan Ringan', Kategori::first()->nama);
        $this->assertSame('Botol', Satuan::where('nama', 'Botol')->first()->nama);
        $this->assertSame('Dus', SatuanKemasan::where('nama', 'Dus')->first()->nama);
        $this->assertSame('Toko Adi', Supplier::first()->nama);
    }

    public function test_nama_master_data_yang_diubah_juga_dibesar_kapital(): void
    {
        $admin = $this->admin();

        $satuan = $this->buatSatuan('pcs');

        $this->actingAs($admin)->put(route('master.satuan.ubah', $satuan), [
            'nama' => 'lusin',
        ])->assertRedirect(route('master.satuan'));

        $this->assertSame('Lusin', $satuan->refresh()->nama);
    }

    /* ---------------- Hak akses Gudang ---------------- */

    public function test_gudang_bisa_membuka_stok_pembelian_dan_supplier(): void
    {
        $gudang = $this->gudang();

        foreach ([
            'stok.index',
            'stok.create',
            'pembelian.index',
            'pembelian.create',
            'master.supplier',
            'master.supplier.create',
            'home',
        ] as $rute) {
            $this->actingAs($gudang)->get(route($rute))->assertOk("Gudang harus bisa membuka $rute");
        }
    }

    public function test_gudang_tidak_bisa_membuka_halaman_admin(): void
    {
        $gudang = $this->gudang();

        foreach ([
            'keuangan.index',
            'keuangan.laba-rugi',
            'keuangan.buku-kas',
            'keuangan.pengeluaran',
            'users.index',
            'master.kategori',
            'master.satuan',
            'master.satuan-kemasan',
        ] as $rute) {
            $this->actingAs($gudang)
                ->get(route($rute))
                ->assertRedirect(route('home'), "Gudang tidak boleh membuka $rute");
        }
    }

    public function test_gudang_tidak_bisa_membuat_produk_tanpa_master_data(): void
    {
        // Gudang boleh tambah produk, tapi tetap harus pilih kategori &
        // supplier dari master data.
        $gudang = $this->gudang();
        $this->buatSatuan('pcs');

        $this->actingAs($gudang)->post(route('stok.store'), [
            'barcode' => 'GUD-1',
            'nama_produk' => 'Tanpa Kategori',
            'satuan' => 'pcs',
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 1,
        ])->assertSessionHasErrors(['kategori_id', 'supplier_id']);
    }

    public function test_gudang_tidak_bisa_membatalkan_transaksi(): void
    {
        $gudang = $this->gudang();

        // Transaksi harus benar-benar ada, kalau tidak jawabannya 404 dan
        // bukan "gudang tidak boleh akses".
        $transaksi = Transaksi::create([
            'user_id' => $gudang->id,
            'nomor_transaksi' => 'TRX-TEST-BATAL',
            'detail_produk' => [],
            'grand_total' => 10000,
            'bayar' => 10000,
            'kembali' => 0,
        ]);

        $this->actingAs($gudang)
            ->post(route('transaksi.batal', $transaksi))
            ->assertRedirect(route('home'));
    }

    public function test_kasir_tidak_bisa_membuka_stok_create(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)
            ->get(route('stok.create'))
            ->assertRedirect(route('home'));
    }

    public function test_admin_bisa_membuat_akun_dengan_peran_gudang(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Budi Gudang',
            'username' => 'budi_gudang',
            'email' => 'budi@example.test',
            'password' => 'rahasia12345',
            'password_confirmation' => 'rahasia12345',
            'role' => User::ROLE_GUDANG,
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'budi_gudang',
            'role' => User::ROLE_GUDANG,
        ]);
    }

    public function test_form_akun_menawarkan_opsi_gudang(): void
    {
        $html = $this->actingAs($this->admin())->get(route('users.create'))->assertOk()->getContent();

        $this->assertStringContainsString('value="gudang"', $html);
        $this->assertStringContainsString('Gudang', $html);
    }

    public function test_sidebar_gudang_hanya_4_menu(): void
    {
        // Gudang: Beranda, Produk & Stok, Pembelian, Supplier. Tidak ada
        // Kasir / Riwayat / Laporan / Tutup Buku.
        $gudang = $this->gudang();

        $html = $this->actingAs($gudang)->get(route('home'))->assertOk()->getContent();

        // Ambil isi menu sidebar saja.
        $m = preg_match('/<nav class="sidebar".*?<\/nav>/s', $html, $cocok);
        $this->assertNotFalse($m);
        $sidebar = $cocok[0];

        // Label menu berada di baris sendiri setelah tag <a>, jadi
        // diambil dulu supaya tidak ada whitespace di sekitarnya.
        // `&amp;` di-decode supaya "Produk & Stok" bisa dibandingkan.
        preg_match_all('/<a[^>]*>\s*([^<]+?)\s*<\/a>/s', $sidebar, $m2);
        $menuTersedia = array_map(
            fn ($m) => html_entity_decode(trim($m), ENT_QUOTES, 'UTF-8'),
            $m2[1]
        );

        foreach (['Beranda', 'Produk & Stok', 'Pembelian', 'Supplier'] as $menu) {
            $this->assertContains($menu, $menuTersedia, "Menu {$menu} harus ada untuk Gudang.");
        }

        foreach (['Kasir', 'Riwayat Transaksi', 'Laporan', 'Tutup Buku', 'Keuangan', 'Manajemen Karyawan'] as $menu) {
            $this->assertNotContains($menu, $menuTersedia, "Menu {$menu} tidak boleh ada untuk Gudang.");
        }
    }

    /* ---------------- Detail produk read-only ---------------- */

    public function test_halaman_detail_produk_tidak_punya_form_ubah_stok(): void
    {
        $admin = $this->admin();

        $kategori = $this->buatKategori('Minuman');
        $supplier = $this->buatSupplier('Toko Besar');
        $this->buatSatuan('pcs');

        $produk = Stok::create([
            'barcode' => 'RO-1',
            'nama_produk' => 'Kopi Read Only',
            'satuan' => 'pcs',
            'kategori' => 'Minuman',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 5,
            'stok_minimal' => 1,
        ]);

        $html = $this->actingAs($admin)->get(route('stok.show', $produk))->assertOk()->getContent();

        // Container yang bisa mengubah stok harus hilang.
        $this->assertStringNotContainsString('Tambah Stok (Barang Datang)', $html);
        $this->assertStringNotContainsString('Penyesuaian Stok', $html);
        $this->assertStringNotContainsString(route('stok.tambah', $produk), $html);
        $this->assertStringNotContainsString(route('stok.sesuaikan', $produk), $html);

        // Tapi halaman edit produk & riwayat stok tetap ada.
        $this->assertStringContainsString('Riwayat Stok', $html);
    }

    /* ---------------- Perbaikan TypeError kategori ---------------- */

    public function test_halaman_edit_produk_tidak_error(): void
    {
        // Relasi kategori TIDAK boleh bernama sama dengan kolom teks
        // `kategori`: kalau sama, Eloquent mengembalikan string dan
        // kodeBarangOtomatis() melempar TypeError.
        $admin = $this->admin();

        $kategori = $this->buatKategori('Minuman');
        $supplier = $this->buatSupplier('Toko Besar');
        $this->buatSatuan('pcs');

        $produk = Stok::create([
            'barcode' => 'EDIT-1',
            'nama_produk' => 'Kopi Edit',
            'satuan' => 'pcs',
            'kategori' => 'Minuman',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 5,
            'stok_minimal' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('stok.edit', $produk))
            ->assertOk()
            ->assertSee('Kopi Edit');
    }
}
