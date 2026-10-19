<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah kolom audit pada `transaksis`.
     *
     * Setiap langkah dijaga `hasColumn`/pemeriksaan index: MySQL tidak bisa
     * me-rollback DDL, jadi kalau satu ALTER gagal di tengah, sisa kolom yang
     * sudah terbentuk akan membuat migrasi berikutnya gagal selamanya dengan
     * "Duplicate column". Dengan penjagaan ini, migrasi aman diulang.
     */
    public function up(): void
    {
        // Siapa kasir yang mencatat transaksi. Nullable agar transaksi lama
        // (sebelum kolom ini ada) tetap valid.
        if (! Schema::hasColumn('transaksis', 'user_id')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('transaksis', 'dibatalkan_pada')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->timestamp('dibatalkan_pada')->nullable()->after('kembali');
            });
        }

        if (! Schema::hasColumn('transaksis', 'dibatalkan_oleh')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->foreignId('dibatalkan_oleh')->nullable()->after('dibatalkan_pada')
                    ->constrained('users')->nullOnDelete();
            });
        }

        // Nomor nota tidak lagi dari `time()` (bentrok dalam detik yang sama),
        // jadi panjangnya perlu ruang untuk komponen acak.
        //
        // `change()` dan penambahan index dipisah: kalau digabung
        // (`->string(..)->unique()->change()`), Laravel mencoba menambah ulang
        // index unique yang sudah ada dan gagal dengan "Duplicate key name".
        if ($this->panjangKolom('transaksis', 'nomor_transaksi') < 64) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->string('nomor_transaksi', 64)->change();
            });
        }

        if (! $this->punyaIndex('transaksis', 'transaksis_nomor_transaksi_unique')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->unique('nomor_transaksi');
            });
        }

        // Laporan dan riwayat selalu menyaring/mengurutkan per tanggal.
        if (! $this->punyaIndex('transaksis', 'transaksis_created_at_index')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transaksis', 'dibatalkan_oleh')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->dropForeign(['dibatalkan_oleh']);
            });
        }

        if (Schema::hasColumn('transaksis', 'user_id')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        $kolom = array_values(array_filter(
            ['user_id', 'dibatalkan_pada', 'dibatalkan_oleh'],
            fn (string $nama) => Schema::hasColumn('transaksis', $nama)
        ));

        if ($kolom !== []) {
            Schema::table('transaksis', function (Blueprint $table) use ($kolom) {
                $table->dropColumn($kolom);
            });
        }
    }

    private function punyaIndex(string $tabel, string $nama): bool
    {
        return collect(Schema::getIndexes($tabel))
            ->contains(fn (array $index) => ($index['name'] ?? null) === $nama);
    }

    /**
     * Panjang kolom varchar saat ini. Dipakai agar `change()` hanya dijalankan
     * kalau memang perlu — MySQL tidak bisa rollback DDL, jadi setiap ALTER
     * yang diulang berisiko gagal pada percobaan kedua.
     */
    private function panjangKolom(string $tabel, string $kolom): int
    {
        $hasil = DB::selectOne(
            'select CHARACTER_MAXIMUM_LENGTH as panjang
               from information_schema.COLUMNS
              where TABLE_SCHEMA = database() and TABLE_NAME = ? and COLUMN_NAME = ?',
            [$tabel, $kolom]
        );

        return (int) ($hasil->panjang ?? 0);
    }
};
