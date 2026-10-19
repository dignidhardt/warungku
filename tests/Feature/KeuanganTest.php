<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\Stok;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\LaporanService;
use App\Services\ReturService;
use App\Services\TransaksiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test fitur keuangan: pembelian, pengeluaran, prive, laba rugi, retur,
 * dan tutup buku.
 *
 * Fokus test ini bukan "halaman bisa dibuka" (itu sudah diuji skrip HTTP),
 * tapi ATURAN BISNISNYA: prive bukan biaya, diskon mengurangi laba, harga
 * pokok memakai harga beli saat transaksi, dan selisih kas wajib ada
 * penjelasannya.
 */
class KeuanganTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function kasir(): User
    {
        return User::factory()->create(['role' => User::ROLE_KASIR]);
    }

    private function produk(array $ubah = []): Stok
    {
        return Stok::create(array_merge([
            'barcode' => 'K-001',
            'nama_produk' => 'Kopi Sachet',
            'satuan' => 'pcs',
            'kategori' => 'Minuman',
            'harga_beli' => 2000,
            'harga_jual' => 3000,
            'stok' => 20,
            'stok_minimal' => 5,
        ], $ubah));
    }

    /* ---------------- Pembelian ---------------- */

    public function test_pembelian_menambah_stok_dan_memperbarui_harga_beli(): void
    {
        $admin = $this->admin();
        $produk = $this->produk(['stok' => 10, 'harga_beli' => 2000]);

        $this->actingAs($admin)->post(route('pembelian.store'), [
            'supplier' => 'Toko Besar',
            'tanggal' => now()->toDateString(),
            'items' => [
                ['id' => $produk->id, 'qty' => 5, 'harga_beli' => 2500],
            ],
        ])->assertRedirect();

        $produk->refresh();

        $this->assertSame(15, $produk->stok, 'Stok harus bertambah sesuai jumlah pembelian.');
        $this->assertSame(2500, $produk->harga_beli, 'Harga beli ikut mengikuti harga kulakan terakhir.');
        $this->assertDatabaseHas('stok_mutasis', [
            'stok_id' => $produk->id,
            'jumlah' => 5,
            'keterangan' => 'Pembelian '.Pembelian::first()->nomor_pembelian,
        ]);
    }

    public function test_pembelian_mengubah_qty_ke_satuan_dasar(): void
    {
        $admin = $this->admin();
        // 1 karung = 25 kg. Membeli 2 karung harus menambah 50 kg, bukan 2.
        $produk = $this->produk([
            'satuan' => 'kg',
            'satuan_kemasan' => 'karung',
            'isi_kemasan' => 25,
            'stok' => 0,
        ]);

        $this->actingAs($admin)->post(route('pembelian.store'), [
            'tanggal' => now()->toDateString(),
            'items' => [
                ['id' => $produk->id, 'qty' => 2, 'harga_beli' => 10000, 'satuan' => 'karung'],
            ],
        ])->assertRedirect();

        $produk->refresh();

        $this->assertSame(50, $produk->stok, '2 karung x 25 kg harus jadi 50 kg.');
    }

    public function test_pembatalan_pembelian_menolak_kalau_stok_sudah_terpakai(): void
    {
        $admin = $this->admin();
        $produk = $this->produk(['stok' => 10]);

        // Beli 15 pcs (total stok 25).
        $this->actingAs($admin)->post(route('pembelian.store'), [
            'tanggal' => now()->toDateString(),
            'items' => [
                ['id' => $produk->id, 'qty' => 15, 'harga_beli' => 2000],
            ],
        ])->assertRedirect();

        $pembelian = Pembelian::first();
        $this->assertSame(25, $produk->refresh()->stok);

        // Lalu terjual 22 pcs, tersisa 3 — lebih sedikit dari jumlah pembelian.
        // Menarik 15 pcs akan membuat stok minus, jadi harus ditolak.
        app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 22]], 66000, $admin
        );
        $this->assertSame(3, $produk->refresh()->stok);

        $this->actingAs($admin)->delete(route('pembelian.batal', $pembelian))
            ->assertSessionHas('error');

        $this->assertSame(3, $produk->refresh()->stok, 'Stok tidak boleh ditarik kalau sudah terpakai.');
        $this->assertDatabaseHas('pembelians', ['id' => $pembelian->id]);
    }

    public function test_kasir_tidak_bisa_mencatat_pembelian(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)->get(route('pembelian.index'))->assertRedirect(route('home'));
    }

    /* ---------------- Pengeluaran & prive ---------------- */

    public function test_prive_tercatat_sebagai_prive_bukan_biaya_operasional(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('keuangan.pengeluaran.simpan'), [
            'jenis' => Pengeluaran::JENIS_PRIVE,
            'kategori' => 'Ambil uang pribadi',
            'tanggal' => now()->toDateString(),
            'jumlah' => 100000,
        ])->assertRedirect();

        $this->assertDatabaseHas('pengeluarans', [
            'jenis' => Pengeluaran::JENIS_PRIVE,
            'jumlah' => 100000,
        ]);
    }

    public function test_prive_tidak_mengurangi_laba_bersih(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();

        // Penjualan 3 x 3000 = 9000, HPP = 3 x 2000 = 6000, laba kotor 3000.
        app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 3]], 9000, $admin
        );

        // Pengeluaran operasional 1000 -> laba bersih 2000.
        Pengeluaran::create([
            'jenis' => Pengeluaran::JENIS_OPERASIONAL,
            'kategori' => 'Listrik',
            'tanggal' => now()->toDateString(),
            'jumlah' => 1000,
            'user_id' => $admin->id,
        ]);

        $sebelumPrive = app(LaporanService::class)->labaRugi(now()->month, now()->year);
        $this->assertSame(2000, $sebelumPrive['laba_bersih']);

        // Prive 500000: uang keluar, tapi TIDAK boleh jadi biaya usaha.
        Pengeluaran::create([
            'jenis' => Pengeluaran::JENIS_PRIVE,
            'kategori' => 'Ambil uang pribadi',
            'tanggal' => now()->toDateString(),
            'jumlah' => 500000,
            'user_id' => $admin->id,
        ]);

        $sesudahPrive = app(LaporanService::class)->labaRugi(now()->month, now()->year);

        $this->assertSame(2000, $sesudahPrive['laba_bersih'], 'Prive tidak boleh mengurangi laba.');
        $this->assertSame(500000, $sesudahPrive['prive']);
        $this->assertSame(1000, $sesudahPrive['biaya_operasional']);
    }

    /* ---------------- Diskon ---------------- */

    public function test_diskon_mengurangi_total_dan_laba(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();

        $trx = app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 2]], 5000, $admin, 1000
        );

        $this->assertSame(5000, $trx->grand_total, 'Total = 6000 - diskon 1000.');
        $this->assertSame(1000, $trx->diskon);
        $this->assertSame(0, $trx->kembali);

        // Laba kotor: (3000-2000)*2 = 2000, dikurangi diskon 1000 = 1000.
        $laba = app(LaporanService::class)->labaRugi(now()->month, now()->year);
        $this->assertSame(1000, $laba['laba_kotor']);
    }

    public function test_diskon_tidak_boleh_melebihi_nilai_belanja(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();

        // Diskon 999999 untuk belanja 3000. Diskon dibatasi nilai belanja,
        // jadi hasilnya diskon 3000 (gratis) dan total 0 — bukan total
        // negatif yang akan membuat laporan omzet rusak.
        $trx = app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 1]], 3000, $admin, 999999
        );

        $this->assertSame(3000, $trx->diskon, 'Diskon dibatasi nilai belanja.');
        $this->assertSame(0, $trx->grand_total, 'Total tidak boleh negatif.');
        $this->assertGreaterThanOrEqual(0, $trx->grand_total);
    }

    public function test_pembayaran_non_tunai_tidak_punya_kembalian(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();

        $trx = app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 1]], 999999, $admin, 0, Transaksi::METODE_QRIS
        );

        $this->assertSame(3000, $trx->bayar);
        $this->assertSame(0, $trx->kembali);
        $this->assertSame('qris', $trx->metode_bayar);
    }

    /* ---------------- Harga pokok historis ---------------- */

    public function test_harga_pokok_memakai_harga_beli_saat_transaksi(): void
    {
        $admin = $this->admin();
        $produk = $this->produk(['harga_beli' => 2000]);

        app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 2]], 6000, $admin
        );

        // Harga beli produk naik setelah penjualan. Laporan bulan ini tidak
        // boleh ikut berubah, karena HPP memakai harga yang tersimpan di nota.
        $produk->update(['harga_beli' => 2500]);

        $laba = app(LaporanService::class)->labaRugi(now()->month, now()->year);
        $this->assertSame(4000, $laba['hpp'], 'HPP harus 2 x 2000 (harga saat transaksi).');
        $this->assertSame(2000, $laba['laba_kotor']);
    }

    /* ---------------- Retur ---------------- */

    public function test_retur_mengembalikan_stok_dan_mengurangi_omzet(): void
    {
        $admin = $this->admin();
        $produk = $this->produk(['stok' => 20]);

        $trx = app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 5]], 15000, $admin
        );

        $this->assertSame(15, $produk->refresh()->stok);

        $retur = app(ReturService::class)->simpan(
            $trx, [['id' => $produk->id, 'qty' => 2]], 'Barang rusak', $admin
        );

        $this->assertSame(6000, $retur->total);
        $this->assertSame(17, $produk->refresh()->stok, 'Stok harus kembali 2 pcs.');
        $this->assertSame(6000, $trx->refresh()->dikembalikan);

        $laba = app(LaporanService::class)->labaRugi(now()->month, now()->year);
        $this->assertSame(9000, $laba['omzet_bersih'], 'Omzet bersih = 15000 - 6000.');
    }

    public function test_retur_tidak_boleh_melebihi_yang_dibeli(): void
    {
        $admin = $this->admin();
        $produk = $this->produk(['stok' => 20]);

        $trx = app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 2]], 6000, $admin
        );

        $this->expectException(\RuntimeException::class);

        app(ReturService::class)->simpan(
            $trx, [['id' => $produk->id, 'qty' => 5]], 'Kelebihan', $admin
        );
    }

    public function test_retur_kedua_ditolak_karena_melebihi_sisa(): void
    {
        $admin = $this->admin();
        $produk = $this->produk(['stok' => 20]);

        $trx = app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 3]], 9000, $admin
        );

        $service = app(ReturService::class);
        $service->simpan($trx, [['id' => $produk->id, 'qty' => 2]], 'Rusak', $admin);

        $this->expectException(\RuntimeException::class);
        $service->simpan($trx, [['id' => $produk->id, 'qty' => 2]], 'Rusak lagi', $admin);
    }

    /* ---------------- Tutup buku ---------------- */

    public function test_tutup_buku_menyimpan_selisih_dengan_catatan(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();

        app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 1]], 3000, $admin
        );

        $this->actingAs($admin)->post(route('keuangan.tutup-buku.simpan'), [
            'tanggal' => now()->toDateString(),
            'kas_fisik' => 2500,
            'catatan' => 'Ada kembalian yang salah hitung',
        ])->assertRedirect();

        $this->assertDatabaseHas('tutup_bukus', [
            'tanggal' => now()->toDateString(),
            'kas_seharusnya' => 3000,
            'kas_fisik' => 2500,
            'selisih' => -500,
            'catatan' => 'Ada kembalian yang salah hitung',
        ]);
    }

    public function test_tutup_buku_tanpa_catatan_ditolak_kalau_uang_tidak_cocok(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();

        app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 1]], 3000, $admin
        );

        $this->actingAs($admin)->post(route('keuangan.tutup-buku.simpan'), [
            'tanggal' => now()->toDateString(),
            'kas_fisik' => 2500,
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('tutup_bukus', 0);
    }

    public function test_kas_seharusnya_bisa_negatif_kalau_pengeluaran_melebihi_penjualan(): void
    {
        $admin = $this->admin();

        Pengeluaran::create([
            'jenis' => Pengeluaran::JENIS_OPERASIONAL,
            'kategori' => 'Listrik',
            'tanggal' => now()->toDateString(),
            'jumlah' => 500000,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('keuangan.tutup-buku.simpan'), [
            'tanggal' => now()->toDateString(),
            'kas_fisik' => 0,
            'catatan' => 'Belum ada omzet hari ini',
        ])->assertRedirect();

        $this->assertDatabaseHas('tutup_bukus', [
            'tanggal' => now()->toDateString(),
            'kas_seharusnya' => -500000,
        ]);
    }

    /* ---------------- Buku kas ---------------- */

    public function test_buku_kas_mencatat_semua_arus_uang(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();

        app(TransaksiService::class)->simpanPenjualan(
            [['id' => $produk->id, 'qty' => 1]], 3000, $admin
        );

        Pengeluaran::create([
            'jenis' => Pengeluaran::JENIS_OPERASIONAL,
            'kategori' => 'Listrik',
            'tanggal' => now()->toDateString(),
            'jumlah' => 1000,
            'user_id' => $admin->id,
        ]);

        $baris = app(LaporanService::class)->bukuKas(now()->month, now()->year);

        $this->assertCount(2, $baris);
        $this->assertSame(3000, (int) $baris->sum('masuk'));
        $this->assertSame(1000, (int) $baris->sum('keluar'));
    }

    public function test_kasir_tidak_bisa_melihat_laba_rugi(): void
    {
        $kasir = $this->kasir();

        $this->actingAs($kasir)->get(route('keuangan.laba-rugi'))->assertRedirect(route('home'));
    }
}
