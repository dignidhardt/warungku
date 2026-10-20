<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Pagar pengaman HARUS dicek SEBELUM parent::setUp().
        //
        // Alasannya: `RefreshDatabase` mulai bekerja di parent::setUp(). Pada
        // database yang belum di-migrate, ia menjalankan `migrate:fresh` —
        // yang MENGHAPUS seluruh tabel. Kalau guard dijalankan setelahnya,
        // damage sudah terjadi sebelum guard sempat menolak.
        //
        // Urutan yang benar: cek dulu, baru boleh database disentuh.
        $this->pastikanDatabaseTest();

        parent::setUp();
    }

    /**
     * Pagar pengaman: `RefreshDatabase` menghapus seluruh tabel di database
     * yang sedang terhubung. Kalau nama database tidak mengandung "test",
     * hentikan test sekarang juga daripada menghapus data dev atau live.
     *
     * Ini penting karena environment proses container menang atas phpunit.xml,
     * sehingga satu perintah tanpa `-e DB_DATABASE=warungku_test` bisa
     * mengarah ke database yang salah.
     */
    private function pastikanDatabaseTest(): void
    {
        // Helper `config()` baru tersedia setelah parent::setUp() menyalakan
        // aplikasi. Karena guard ini jalan lebih dulu, konfigurasi dibaca
        // langsung dari variabel environment.
        $koneksi = getenv('DB_CONNECTION') ?: 'mysql';
        $nama = (string) (getenv('DB_DATABASE') ?: '');

        // SQLite in-memory (dipakai saat menjalankan test di host) tidak
        // menyentuh data apa pun, jadi selalu aman.
        if (in_array($koneksi, ['sqlite', 'testing'], true)) {
            return;
        }

        // `phpunit.xml` menentukan DB_DATABASE=warungku_test, tapi variabel
        // environment proses MENANG atas file itu. Karena itu nama database
        // di sini dibaca dari environment, bukan dari file konfigurasi.
        if ($nama === '') {
            $nama = (string) (getenv('DB_DATABASE') ?: 'warungku_test');
        }

        if (! str_contains(strtolower($nama), 'test')) {
            $this->fail(
                "Test dibatalkan: database aktif '{$nama}' bukan database test. ".
                'Jalankan lewat `make test` (DB_DATABASE=warungku_test).'
            );
        }
    }
}
