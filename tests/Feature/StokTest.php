<?php

namespace Tests\Feature;

use App\Models\Stok;
use App\Models\StokMutasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StokTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function produk(array $ubah = []): Stok
    {
        return Stok::create(array_merge([
            'barcode' => 'BRG-001',
            'nama_produk' => 'Kopi Sachet',
            'kategori' => 'Minuman',
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

        $this->actingAs($admin)->post(route('stok.store'), [
            'barcode' => 'BRG-999',
            'nama_produk' => 'Teh Botol',
            'kategori' => 'Minuman',
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

        $this->actingAs($this->admin())->post(route('stok.store'), [
            'barcode' => 'SAMA-1',
            'nama_produk' => 'Produk Lain',
            'kategori' => 'Snack',
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 1,
        ])->assertSessionHasErrors('barcode');
    }

    public function test_harga_jual_di_bawah_harga_beli_ditolak(): void
    {
        $this->actingAs($this->admin())->post(route('stok.store'), [
            'barcode' => 'BRG-002',
            'nama_produk' => 'Rugi',
            'kategori' => 'Snack',
            'harga_beli' => 5000,
            'harga_jual' => 4000,
            'stok' => 1,
        ])->assertSessionHasErrors('harga_jual');
    }

    public function test_kasir_tidak_bisa_menambah_produk(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->actingAs($kasir)->post(route('stok.store'), [
            'barcode' => 'BRG-003',
            'nama_produk' => 'Tidak Boleh',
            'kategori' => 'Snack',
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
            'kategori' => 'Minuman',
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
}
