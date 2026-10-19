<?php

namespace Tests\Feature;

use App\Models\Stok;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private function jual(User $kasir, Stok $produk, int $qty, int $bayar = 100000): Transaksi
    {
        $this->actingAs($kasir)->postJson(route('transaksi.store'), [
            'keranjang' => json_encode([['id' => $produk->id, 'qty' => $qty]]),
            'bayar' => $bayar,
        ])->assertOk();

        return Transaksi::latest('id')->firstOrFail();
    }

    public function test_laporan_menghitung_omzet_dan_laba_kotor(): void
    {
        $admin = User::factory()->admin()->create();
        $produk = Stok::create([
            'barcode' => 'L-1',
            'nama_produk' => 'Kopi',
            'kategori' => 'Minuman',
            'harga_beli' => 2000,
            'harga_jual' => 3000,
            'stok' => 20,
            'stok_minimal' => 2,
        ]);

        $this->jual($admin, $produk, 4);
        $this->jual($admin, $produk, 6);

        $response = $this->actingAs($admin)->get(route('laporan.index'))->assertOk();

        // 10 pcs x 3.000 = 30.000 omzet; laba (3000-2000) x 10 = 10.000.
        $response->assertViewHas('ringkasan', function (array $ringkasan) {
            return $ringkasan['omzet'] === 30000
                && $ringkasan['laba'] === 10000
                && $ringkasan['jumlah_transaksi'] === 2
                && $ringkasan['jumlah_item'] === 10;
        });
    }

    public function test_export_excel_menghasilkan_file_xlsx(): void
    {
        $admin = User::factory()->admin()->create();
        $produk = Stok::create([
            'barcode' => 'L-2',
            'nama_produk' => 'Teh',
            'kategori' => 'Minuman',
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'stok' => 10,
            'stok_minimal' => 1,
        ]);

        $this->jual($admin, $produk, 1);

        $response = $this->actingAs($admin)->get(route('laporan.excel'));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_export_pdf_menghasilkan_file_pdf(): void
    {
        $admin = User::factory()->admin()->create();
        $produk = Stok::create([
            'barcode' => 'L-3',
            'nama_produk' => 'Sabun',
            'kategori' => 'Rumah',
            'harga_beli' => 2000,
            'harga_jual' => 4500,
            'stok' => 5,
            'stok_minimal' => 1,
        ]);

        $this->jual($admin, $produk, 2);

        $response = $this->actingAs($admin)->get(route('laporan.pdf'));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_produk_terlaris_diurutkan_dari_yang_paling_banyak(): void
    {
        $admin = User::factory()->admin()->create();

        $kopi = Stok::create([
            'barcode' => 'P-1', 'nama_produk' => 'Kopi', 'kategori' => 'Minuman',
            'harga_beli' => 1000, 'harga_jual' => 2000, 'stok' => 50, 'stok_minimal' => 1,
        ]);

        $teh = Stok::create([
            'barcode' => 'P-2', 'nama_produk' => 'Teh', 'kategori' => 'Minuman',
            'harga_beli' => 1000, 'harga_jual' => 2000, 'stok' => 50, 'stok_minimal' => 1,
        ]);

        $this->jual($admin, $teh, 8);
        $this->jual($admin, $kopi, 2);

        $this->actingAs($admin)
            ->get(route('laporan.index'))
            ->assertViewHas('produkTerlaris', function ($terlaris) {
                return $terlaris->first()['nama'] === 'Teh'
                    && $terlaris->first()['qty'] === 8;
            });
    }

    public function test_filter_bulan_dan_tahun_di_luar_rentang_dinormalkan(): void
    {
        $admin = User::factory()->admin()->create();

        // ?bulan=99&tahun=1 tidak boleh membuat halaman error.
        $this->actingAs($admin)
            ->get(route('laporan.index', ['bulan' => 99, 'tahun' => 1]))
            ->assertOk()
            ->assertViewHas('bulanSelected', (int) date('n'))
            ->assertViewHas('tahunSelected', (int) date('Y'));
    }
}
