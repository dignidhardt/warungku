<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->pastikanDatabaseTest();
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
        $koneksi = config('database.default');

        // SQLite in-memory (dipakai saat menjalankan test di host) tidak
        // menyentuh data apa pun, jadi selalu aman.
        if (in_array($koneksi, ['sqlite', 'testing'], true)) {
            return;
        }

        $nama = (string) config("database.connections.{$koneksi}.database");

        if (! str_contains(strtolower($nama), 'test')) {
            $this->fail(
                "Test dibatalkan: database aktif '{$nama}' bukan database test. ".
                'Jalankan lewat `make test` (DB_DATABASE=warungku_test).'
            );
        }
    }
}
