<?php

namespace Tests\Feature;

use App\Models\Stok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test halaman kasir.
 *
 * Fokusnya: halaman ini punya blok JavaScript besar, dan Blade punya
 * kebiasaan yang rapi sekali untuk menjebak: dia mem-proses directive
 * (`@json`, `@if`, ...) yang PENAMPILANNYA cuma ada di dalam komentar atau
 * di dalam string. Awalnya daftar produk dirakit langsung di view dengan
 * `@json($produk->map(fn ($p) => [...]))` — Blade mengurai `=>` dan `[` sebagai
 * directive-nya sendiri, hasilnya "Unclosed '['" dan halaman 500.
 *
 * Test di bawah mengunci supaya view ini (dan view lain) tetap bisa
 * dirender tanpa error Blade.
 */
class TransaksiHalamanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_kasir_bisa_dirender(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->actingAs($kasir)
            ->get(route('transaksi.index'))
            ->assertOk()
            ->assertSee('Keranjang');
    }

    public function test_halaman_kasir_rusak_jika_produk_kosong(): void
    {
        // Kasus yang pernah jadi bug: tidak ada produk, view harus tetap
        // bisa dirender (JSON kosong, bukan error).
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->actingAs($kasir)
            ->get(route('transaksi.index'))
            ->assertOk()
            ->assertSee('Belum ada produk yang stoknya tersedia');
    }

    public function test_daftar_produk_dikirim_sebagai_json_yang_valid(): void
    {
        Stok::create([
            'barcode' => 'JSON-1',
            'nama_produk' => 'Kopi Uji JSON',
            'satuan' => 'pcs',
            'kategori' => 'Minuman',
            'harga_beli' => 1000,
            'harga_jual' => 2500,
            'stok' => 10,
            'stok_minimal' => 5,
        ]);

        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $html = $this->actingAs($kasir)->get(route('transaksi.index'))->getContent();

        // JSON harus muncul sebagai array yang bisa di-parse JavaScript.
        $this->assertMatchesRegularExpression(
            '/const daftarProduk = \[\{"id":\d+/',
            $html,
            'Daftar produk harus dikirim sebagai JSON yang valid untuk JavaScript.'
        );

        // Dan isi JSON-nya harus benar-benar bisa di-decode.
        preg_match('/const daftarProduk = (\[.*?\]);/s', $html, $cocok);
        $this->assertNotEmpty($cocok, 'Blok JSON daftar produk tidak ditemukan.');

        $data = json_decode($cocok[1], true);
        $this->assertIsArray($data);
        $this->assertSame('Kopi Uji JSON', $data[0]['nama']);
        $this->assertSame(2500, $data[0]['harga']);
        $this->assertSame('pcs', $data[0]['satuan']);
    }

    public function test_produk_stok_nol_tidak_dikirim_ke_kasir(): void
    {
        // Produk stok 0 tidak boleh muncul di kasir: kasir tidak bisa
        // menjual barang yang tidak ada.
        Stok::create([
            'barcode' => 'HABIS-1',
            'nama_produk' => 'Produk Habis',
            'satuan' => 'pcs',
            'kategori' => 'Minuman',
            'harga_beli' => 1000,
            'harga_jual' => 2500,
            'stok' => 0,
            'stok_minimal' => 5,
        ]);

        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->actingAs($kasir)
            ->get(route('transaksi.index'))
            ->assertOk()
            ->assertDontSee('Produk Habis');
    }

    /**
     * Test ini yang paling penting: memaksa SEMUA view dirender. Kalau ada
     * view yang salah sintaks Blade-nya, test ini gagal dengan pesan yang
     * menyebut view yang bermasalah — bukan "halaman 500" tanpa penjelasan.
     */
    public function test_semua_view_dasar_bisa_dirender_tanpa_error_blade(): void
    {
        $admin = User::factory()->admin()->create();

        $halaman = [
            'home' => route('home'),
            'transaksi.index' => route('transaksi.index'),
            'transaksi.riwayat' => route('transaksi.riwayat'),
            'stok.index' => route('stok.index'),
            'stok.create' => route('stok.create'),
            'laporan.index' => route('laporan.index'),
            'keuangan.index' => route('keuangan.index'),
            'keuangan.laba-rugi' => route('keuangan.laba-rugi'),
            'keuangan.buku-kas' => route('keuangan.buku-kas'),
            'keuangan.pengeluaran' => route('keuangan.pengeluaran'),
            'keuangan.tutup-buku' => route('keuangan.tutup-buku'),
            'pembelian.index' => route('pembelian.index'),
            'pembelian.create' => route('pembelian.create'),
            'users.index' => route('users.index'),
            'users.create' => route('users.create'),
        ];

        foreach ($halaman as $nama => $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk("Halaman $nama gagal dirender: ".$url);
        }
    }
}
