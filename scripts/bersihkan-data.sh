#!/bin/sh
#
# Mengosongkan data operasional tanpa menghapus akun dan master data.
#
# Dipakai untuk menguji alur aplikasi dari nol: tidak ada transaksi sisa,
# tidak ada produk contoh, tidak ada pengeluaran dari uji sebelumnya.
#
# Yang TIDAK dihapus:
#   - akun (admin & kasir). Kalau akun ikut terhapus, kamu tidak bisa login
#     untuk menguji alurnya — dan itu justru bagian yang mau diuji.
#   - master data (kategori, satuan, satuan kemasan, supplier). Ini data
#     konfigurasi, bukan data operasional: menghapusnya membuat form produk
#     tidak punya pilihan sama sekali.
#
# AUTO_INCREMENT ikut di-reset supaya penomoran nota, pembelian, dan retur
# mulai lagi dari 1. Tanpa itu, nomor nota berikutnya akan lompat ke angka
# besar dan tidak bisa dibandingkan dengan hasil uji sebelumnya.
#
# Perbedaan dari `make fresh`:
#   make fresh  -> migrate:fresh + seed (menghapus tabel, migrate ulang, isi
#                  data contoh). Lebih lambat, tapi mengembalikan kondisi
#                  database ke bentuk awal.
#   script ini  -> hanya menghapus baris. Lebih cepat, dan tidak menyentuh
#                  struktur tabel.

set -eu

DB_HOST="${DB_HOST:-mysql}"
DB_DATABASE="${DB_DATABASE:-warungku_dev}"
DB_USER="${DB_USER:-warungku}"
DB_PASSWORD="${DB_PASSWORD:-secret}"

eksekusi_sql() {
    if [ -n "${MYSQL_CONTAINER:-}" ]; then
        docker exec -i "$MYSQL_CONTAINER" mysql \
            -u"$DB_USER" -p"$DB_PASSWORD" "$DB_DATABASE"
    else
        mysql -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_DATABASE"
    fi
}

echo "[bersih] database: $DB_DATABASE"

# Urutan penghapusan mengikuti dependensi foreign key: anak dulu, lalu induk.
#
# `stok` ikut dihapus: produk contoh dan produk uji adalah data operasional,
# bukan master data. Yang dipertahankan hanya akun + master data (kategori,
# satuan, satuan kemasan, supplier) karena itulah yang dipakai dropdown form
# produk.
eksekusi_sql <<'SQL'
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE retur_items;
TRUNCATE TABLE returs;
TRUNCATE TABLE pembelian_items;
TRUNCATE TABLE pembelians;
TRUNCATE TABLE stok_mutasis;
TRUNCATE TABLE transaksis;
TRUNCATE TABLE tutup_bukus;
TRUNCATE TABLE pengeluarans;
TRUNCATE TABLE stok;

-- Reset penomoran supaya uji berikutnya mulai dari angka 1.
ALTER TABLE retur_items AUTO_INCREMENT = 1;
ALTER TABLE returs AUTO_INCREMENT = 1;
ALTER TABLE pembelian_items AUTO_INCREMENT = 1;
ALTER TABLE pembelians AUTO_INCREMENT = 1;
ALTER TABLE stok_mutasis AUTO_INCREMENT = 1;
ALTER TABLE transaksis AUTO_INCREMENT = 1;
ALTER TABLE tutup_bukus AUTO_INCREMENT = 1;
ALTER TABLE pengeluarans AUTO_INCREMENT = 1;
ALTER TABLE stok AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;
SQL

echo "[bersih] data operasional dikosongkan (akun & master data tetap ada)"
eksekusi_sql <<'SQL'
SELECT
  (SELECT COUNT(*) FROM stok) AS produk,
  (SELECT COUNT(*) FROM transaksis) AS nota,
  (SELECT COUNT(*) FROM stok_mutasis) AS mutasi,
  (SELECT COUNT(*) FROM pengeluarans) AS pengeluaran,
  (SELECT COUNT(*) FROM pembelians) AS pembelian,
  (SELECT COUNT(*) FROM returs) AS retur,
  (SELECT COUNT(*) FROM tutup_bukus) AS tutup_buku,
  (SELECT COUNT(*) FROM users) AS akun,
  (SELECT COUNT(*) FROM kategoris) AS kategori,
  (SELECT COUNT(*) FROM satuans) AS satuan,
  (SELECT COUNT(*) FROM satuan_kemisans) AS satuan_kemasan,
  (SELECT COUNT(*) FROM suppliers) AS supplier;
SQL
