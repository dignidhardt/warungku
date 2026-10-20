<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Satuan;
use App\Models\SatuanKemasan;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuatMasterData;
use Tests\TestCase;

/**
 * Test master data: kategori, satuan, satuan kemasan, supplier.
 *
 * Semua hanya boleh diakses admin. Kasir yang tries harus dikembalikan
 * ke beranda, bukan diberi akses setengah-setengah.
 */
class MasterDataTest extends TestCase
{
    use BuatMasterData;
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function kasir(): User
    {
        return User::factory()->create(['role' => User::ROLE_KASIR]);
    }

    /* ---------------- Akses ---------------- */

    public function test_kasir_tidak_bisa_membuka_halaman_master_data(): void
    {
        $kasir = $this->kasir();

        foreach (['master.kategori', 'master.satuan', 'master.satuan-kemasan', 'master.supplier'] as $rute) {
            $this->actingAs($kasir)
                ->get(route($rute))
                ->assertRedirect(route('home'));
        }
    }

    public function test_admin_bisa_membuka_semua_halaman_master_data(): void
    {
        $admin = $this->admin();

        foreach (['master.kategori', 'master.satuan', 'master.satuan-kemasan', 'master.supplier'] as $rute) {
            $this->actingAs($admin)->get(route($rute))->assertOk();
        }
    }

    /* ---------------- Kategori ---------------- */

    public function test_admin_bisa_menambah_kategori_dengan_kode_barang(): void
    {
        $this->actingAs($this->admin())->post(route('master.kategori.simpan'), [
            'nama' => 'Sembako',
            'kode_barang' => 'AAA',
        ])->assertRedirect(route('master.kategori'));

        $this->assertDatabaseHas('kategoris', [
            'nama' => 'Sembako',
            'kode_barang' => 'AAA',
        ]);
    }

    public function test_kode_barang_kategori_dibuat_otomatis_kosong(): void
    {
        $this->actingAs($this->admin())->post(route('master.kategori.simpan'), [
            'nama' => 'Minuman',
        ])->assertRedirect(route('master.kategori'));

        // "Minuman" -> 3 huruf pertama "MIN".
        $this->assertDatabaseHas('kategoris', [
            'nama' => 'Minuman',
            'kode_barang' => 'MIN',
        ]);
    }

    public function test_kode_barang_kategori_harus_3_karakter(): void
    {
        $this->actingAs($this->admin())->post(route('master.kategori.simpan'), [
            'nama' => 'Sembako',
            'kode_barang' => 'AB',
        ])->assertSessionHasErrors('kode_barang');
    }

    public function test_kode_barang_kategori_ganda_ditolak(): void
    {
        $this->buatKategori('Minuman', 'AAA');

        $this->actingAs($this->admin())->post(route('master.kategori.simpan'), [
            'nama' => 'Lainnya',
            'kode_barang' => 'AAA',
        ])->assertSessionHasErrors('kode_barang');
    }

    public function test_nama_kategori_ganda_ditolak(): void
    {
        $this->buatKategori('Minuman', 'AAA');

        $this->actingAs($this->admin())->post(route('master.kategori.simpan'), [
            'nama' => 'Minuman',
        ])->assertSessionHasErrors('nama');
    }

    /* ---------------- Satuan & Satuan Kemasan ---------------- */

    public function test_admin_bisa_menambah_satuan_dan_satuan_kemasan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('master.satuan.simpan'), ['nama' => 'karton'])
            ->assertRedirect(route('master.satuan'));

        $this->actingAs($admin)->post(route('master.satuan-kemasan.simpan'), ['nama' => 'palet'])
            ->assertRedirect(route('master.satuan-kemasan'));

        $this->assertDatabaseHas('satuans', ['nama' => 'karton']);
        $this->assertDatabaseHas('satuan_kemisans', ['nama' => 'palet']);
    }

    public function test_satuan_ganda_ditolak(): void
    {
        $this->buatSatuan('pcs');

        $this->actingAs($this->admin())->post(route('master.satuan.simpan'), ['nama' => 'pcs'])
            ->assertSessionHasErrors('nama');
    }

    /* ---------------- Supplier ---------------- */

    public function test_admin_bisa_menambah_supplier_dengan_kode_otomatis(): void
    {
        $this->actingAs($this->admin())->post(route('master.supplier.store'), [
            'nama' => 'Toko Sembako Jaya',
            'telepon' => '0812-3456-7890',
            'alamat' => 'Pasar Induk Blok C',
        ])->assertRedirect(route('master.supplier'));

        $this->assertDatabaseHas('suppliers', [
            'nama' => 'Toko Sembako Jaya',
            'kode' => 'SUP-001',
            'telepon' => '0812-3456-7890',
        ]);
    }

    public function test_kode_supplier_otomatis_bertambah(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('master.supplier.store'), ['nama' => 'Toko A']);
        $this->actingAs($admin)->post(route('master.supplier.store'), ['nama' => 'Toko B']);

        $this->assertDatabaseHas('suppliers', ['nama' => 'Toko A', 'kode' => 'SUP-001']);
        $this->assertDatabaseHas('suppliers', ['nama' => 'Toko B', 'kode' => 'SUP-002']);
    }

    public function test_nama_supplier_ganda_ditolak(): void
    {
        $this->buatSupplier('Toko Besar');

        $this->actingAs($this->admin())->post(route('master.supplier.store'), ['nama' => 'Toko Besar'])
            ->assertSessionHasErrors('nama');
    }

    public function test_menghapus_supplier_tidak_menghapus_produk(): void
    {
        $supplier = $this->buatSupplier('Toko Besar');

        $kategori = $this->buatKategori('Minuman');
        $this->buatSatuan('pcs');

        $produk = \App\Models\Stok::create([
            'barcode' => 'SUP-1',
            'nama_produk' => 'Kopi',
            'satuan' => 'pcs',
            'kategori' => 'Minuman',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 5,
            'stok_minimal' => 1,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('master.supplier.hapus', $supplier))
            ->assertRedirect(route('master.supplier'));

        // Produk harus tetap ada, supplier-nya jadi kosong.
        $this->assertDatabaseHas('stok', ['id' => $produk->id]);
        $this->assertNull($produk->refresh()->supplier_id);
    }

    public function test_menghapus_kategori_tidak_menghapus_produk(): void
    {
        $kategori = $this->buatKategori('Minuman');
        $supplier = $this->buatSupplier('Toko Besar');
        $this->buatSatuan('pcs');

        $produk = \App\Models\Stok::create([
            'barcode' => 'KAT-1',
            'nama_produk' => 'Kopi',
            'satuan' => 'pcs',
            'kategori' => 'Minuman',
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 5,
            'stok_minimal' => 1,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('master.kategori.hapus', $kategori))
            ->assertRedirect(route('master.kategori'));

        $this->assertDatabaseHas('stok', ['id' => $produk->id]);
        $this->assertNull($produk->refresh()->kategori_id);
    }
}
