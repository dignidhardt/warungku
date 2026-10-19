<?php

namespace Tests\Feature;

use App\Models\Stok;
use App\Models\StokMutasi;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiTest extends TestCase
{
    use RefreshDatabase;

    private function produk(array $ubah = []): Stok
    {
        return Stok::create(array_merge([
            'barcode' => 'BRG-'.fake()->unique()->numerify('###'),
            'nama_produk' => 'Kopi Sachet',
            'kategori' => 'Minuman',
            'harga_beli' => 2000,
            'harga_jual' => 3000,
            'stok' => 10,
            'stok_minimal' => 2,
        ], $ubah));
    }

    private function keranjang(array $items): string
    {
        return json_encode($items);
    }

    public function test_kasir_bisa_menyimpan_transaksi_dan_stok_berkurang(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $produk = $this->produk(['stok' => 10, 'harga_jual' => 3000]);

        $response = $this->actingAs($kasir)->postJson(route('transaksi.store'), [
            'keranjang' => $this->keranjang([['id' => $produk->id, 'qty' => 3]]),
            'bayar' => 10000,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['nomor_transaksi', 'kembali', 'struk_url']);

        $this->assertSame(7, $produk->refresh()->stok);

        $trx = Transaksi::firstOrFail();
        $this->assertSame(9000, $trx->grand_total);
        $this->assertSame(1000, $trx->kembali);
        $this->assertSame($kasir->id, $trx->user_id, 'Kasir wajib tercatat di nota.');

        // Pemotongan stok harus punya jejak mutasi.
        $this->assertDatabaseHas('stok_mutasis', [
            'stok_id' => $produk->id,
            'jenis' => StokMutasi::JENIS_KELUAR,
            'jumlah' => 3,
            'stok_sesudah' => 7,
        ]);
    }

    public function test_harga_diambil_dari_database_bukan_dari_browser(): void
    {
        $kasir = User::factory()->create();
        $produk = $this->produk(['harga_jual' => 5000]);

        // Browser mengirim harga palsu 1 rupiah.
        $this->actingAs($kasir)->postJson(route('transaksi.store'), [
            'keranjang' => json_encode([['id' => $produk->id, 'qty' => 2, 'harga' => 1]]),
            'bayar' => 20000,
        ])->assertOk();

        $this->assertSame(10000, Transaksi::firstOrFail()->grand_total);
    }

    public function test_stok_tidak_cukup_ditolak_dan_tidak_mengubah_apa_pun(): void
    {
        $kasir = User::factory()->create();
        $produk = $this->produk(['stok' => 2]);

        $this->actingAs($kasir)->postJson(route('transaksi.store'), [
            'keranjang' => $this->keranjang([['id' => $produk->id, 'qty' => 5]]),
            'bayar' => 50000,
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->assertSame(2, $produk->refresh()->stok);
        $this->assertSame(0, Transaksi::count());
    }

    public function test_bayar_kurang_ditolak(): void
    {
        $kasir = User::factory()->create();
        $produk = $this->produk(['stok' => 10, 'harga_jual' => 5000]);

        $this->actingAs($kasir)->postJson(route('transaksi.store'), [
            'keranjang' => $this->keranjang([['id' => $produk->id, 'qty' => 2]]),
            'bayar' => 1000,
        ])->assertStatus(422);

        $this->assertSame(10, $produk->refresh()->stok);
        $this->assertSame(0, Transaksi::count());
    }

    public function test_dua_transaksi_berurutan_tidak_bentrok_nomor(): void
    {
        $kasir = User::factory()->create();
        $produk = $this->produk(['stok' => 100]);

        foreach ([1, 2, 3] as $urutan) {
            $this->actingAs($kasir)->postJson(route('transaksi.store'), [
                'keranjang' => $this->keranjang([['id' => $produk->id, 'qty' => 1]]),
                'bayar' => 5000,
            ])->assertOk();
        }

        // Sebelumnya 'TRX-'.time() membuat transaksi ke-2 pada detik yang sama
        // gagal karena unique index. Sekarang nomornya harus unik semua.
        $nomor = Transaksi::pluck('nomor_transaksi');
        $this->assertCount(3, $nomor);
        $this->assertCount(3, $nomor->unique());
    }

    public function test_baris_dengan_produk_sama_digabung_saat_cek_stok(): void
    {
        $kasir = User::factory()->create();
        $produk = $this->produk(['stok' => 4]);

        // Dua baris x 3 pcs = 6 pcs, padahal stok hanya 4. Kalau dicek per
        // baris, keduanya lolos dan stok jadi minus.
        $this->actingAs($kasir)->postJson(route('transaksi.store'), [
            'keranjang' => $this->keranjang([
                ['id' => $produk->id, 'qty' => 3],
                ['id' => $produk->id, 'qty' => 3],
            ]),
            'bayar' => 50000,
        ])->assertStatus(422);

        $this->assertSame(4, $produk->refresh()->stok);
        $this->assertSame(0, Transaksi::count());
    }

    public function test_struk_bisa_dicetak_dan_menampilkan_rincian(): void
    {
        $kasir = User::factory()->create();
        $produk = $this->produk(['nama_produk' => 'Teh Botol', 'harga_jual' => 5000]);

        $this->actingAs($kasir)->postJson(route('transaksi.store'), [
            'keranjang' => $this->keranjang([['id' => $produk->id, 'qty' => 2]]),
            'bayar' => 20000,
        ])->assertOk();

        $trx = Transaksi::firstOrFail();

        $this->actingAs($kasir)
            ->get(route('transaksi.struk', $trx))
            ->assertOk()
            ->assertSee($trx->nomor_transaksi)
            ->assertSee('Teh Botol')
            ->assertSee('10.000');
    }

    public function test_admin_bisa_membatalkan_transaksi_dan_stok_kembali(): void
    {
        $admin = User::factory()->admin()->create();
        $produk = $this->produk(['stok' => 10]);

        $this->actingAs($admin)->postJson(route('transaksi.store'), [
            'keranjang' => $this->keranjang([['id' => $produk->id, 'qty' => 4]]),
            'bayar' => 20000,
        ])->assertOk();

        $trx = Transaksi::firstOrFail();
        $this->assertSame(6, $produk->refresh()->stok);

        $this->actingAs($admin)
            ->post(route('transaksi.batal', $trx))
            ->assertRedirect();

        $this->assertSame(10, $produk->refresh()->stok);

        $trx->refresh();
        $this->assertNotNull($trx->dibatalkan_pada);
        $this->assertSame($admin->id, $trx->dibatalkan_oleh);

        // Nota tidak boleh hilang.
        $this->assertDatabaseHas('transaksis', ['id' => $trx->id]);

        // Pembatalan harus tercatat sebagai stok masuk.
        $this->assertDatabaseHas('stok_mutasis', [
            'stok_id' => $produk->id,
            'jenis' => StokMutasi::JENIS_MASUK,
            'jumlah' => 4,
            'stok_sesudah' => 10,
        ]);
    }

    public function test_kasir_tidak_bisa_membatalkan_transaksi(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $produk = $this->produk(['stok' => 10]);

        $this->actingAs($kasir)->postJson(route('transaksi.store'), [
            'keranjang' => $this->keranjang([['id' => $produk->id, 'qty' => 2]]),
            'bayar' => 10000,
        ])->assertOk();

        $trx = Transaksi::firstOrFail();

        $this->actingAs($kasir)
            ->post(route('transaksi.batal', $trx))
            ->assertRedirect(route('home'));

        $this->assertNull($trx->refresh()->dibatalkan_pada);
    }

    public function test_transaksi_batal_tidak_dihitung_di_laporan(): void
    {
        $admin = User::factory()->admin()->create();
        $produk = $this->produk(['harga_jual' => 10000, 'stok' => 10]);

        $this->actingAs($admin)->postJson(route('transaksi.store'), [
            'keranjang' => $this->keranjang([['id' => $produk->id, 'qty' => 1]]),
            'bayar' => 10000,
        ])->assertOk();

        $trx = Transaksi::firstOrFail();
        $this->actingAs($admin)->post(route('transaksi.batal', $trx));

        $this->actingAs($admin)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('Rp 0', false);
    }

    public function test_riwayat_transaksi_bisa_difilter_nomor_nota(): void
    {
        $kasir = User::factory()->create();
        $produk = $this->produk(['stok' => 50]);

        $this->actingAs($kasir)->postJson(route('transaksi.store'), [
            'keranjang' => $this->keranjang([['id' => $produk->id, 'qty' => 1]]),
            'bayar' => 5000,
        ])->assertOk();

        $nomor = Transaksi::firstOrFail()->nomor_transaksi;

        $this->actingAs($kasir)
            ->get(route('transaksi.riwayat', ['cari' => $nomor]))
            ->assertOk()
            ->assertSee($nomor);

        $this->actingAs($kasir)
            ->get(route('transaksi.riwayat', ['cari' => 'TIDAK-ADA']))
            ->assertOk()
            ->assertSee('Tidak ada transaksi');
    }
}
