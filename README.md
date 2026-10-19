# Warungku

Aplikasi kasir (POS) sederhana untuk toko kelontong/warung: kelola produk dan
stok, catat penjualan tunai, cetak struk, dan lihat laporan harian/bulanan.

Dibangun dengan **Laravel 12** (PHP 8.4) dan **MySQL 8.4**, dijalankan lewat
**Docker**. Tidak butuh Node/npm — CSS dan JavaScript dilayani langsung dari
`public/`.

---

## Menjalankan (development)

Prasyarat: Docker + Docker Compose.

```bash
make up
```

Perintah itu membangun image, menyalakan MySQL dan aplikasi, menjalankan
migrasi, lalu menampilkan status kesehatan.

| Bagian | Alamat |
|---|---|
| Aplikasi | http://127.0.0.1:8010 |
| MySQL | `127.0.0.1:3309` — db `warungku_dev`, user `warungku`, sandi `secret` |

Akun contoh (dibuat oleh seeder):

| Peran | Username | Kata sandi |
|---|---|---|
| Admin | `admin` | `admin12345` |
| Kasir | `kasir` | `kasir12345` |

Isi data contoh produk:

```bash
make seed
```

Perintah lain yang sering dipakai:

```bash
make help       # daftar semua perintah
make logs       # ikuti log aplikasi
make sh         # masuk shell container
make db         # konsol MySQL
make test       # jalankan test (database terpisah)
make fresh      # hapus data dev, migrate + seed ulang
make down       # matikan stack (data tetap aman di volume)
```

**Kode di-mount dari host**, jadi mengedit file langsung terlihat di aplikasi.
Rebuild hanya perlu kalau `Dockerfile` atau `composer.json` berubah.

---

## Struktur environment

Ada tiga environment yang sengaja dipisah total:

| | Dev | Test | Live |
|---|---|---|---|
| Compose | `compose.yaml` | — | `compose.live.yaml` |
| Project | `warungku-dev` | (di dalam dev) | `warungku-live` |
| Database | `warungku_dev` | `warungku_test` | `warungku` |
| Port app | 8010 | — | 8011 |
| Port MySQL | 3309 | — | tidak dipublikasi |
| File env | `.env.docker` | `phpunit.xml` | `.env.live` |
| Kode | mount dari host | mount dari host | disalin ke image |
| Migrasi | otomatis saat `up` | otomatis per test | manual (`make live-migrate`) |

Test memakai database sendiri karena `RefreshDatabase` menghapus seluruh tabel.
`tests/TestCase.php` menolak berjalan kalau nama database yang ter-resolve tidak
mengandung `test`, jadi satu perintah salah tidak bisa menghapus data dev.

---

## Menjalankan (live)

```bash
cp .env.live.example .env.live
# isi APP_KEY (php artisan key:generate --show) dan DB_PASSWORD yang kuat

make live-up
make live-migrate
```

Aplikasi live ada di http://127.0.0.1:8011. Database live **tidak** dipublikasi
ke host. Kode disalin ke image saat build, jadi perubahan file di host tidak
mempengaruhi server yang sedang berjalan — sengaja, agar tidak ada perubahan
tak sengaja pada aplikasi produksi.

Setelah ada perubahan kode yang mau dinaikkan ke live:

```bash
make live-build && make live-up
```

---

## Peta aplikasi

| Halaman | Peran | Keterangan |
|---|---|---|
| Beranda | admin, kasir | Omzet, nota, laba kotor, stok menipis, produk terlaris |
| Kasir | admin, kasir | Keranjang, bayar tunai, simpan → struk |
| Riwayat Transaksi | admin, kasir | Filter tanggal & nomor nota; admin bisa membatalkan |
| Produk & Stok | admin, kasir | Daftar + pencarian; ubah data hanya admin |
| Detail Produk | admin, kasir | Riwayat mutasi stok; tambah/penyesuaian stok hanya admin |
| Laporan | admin, kasir | Omzet, laba kotor, produk terlaris, export Excel & PDF |
| Manajemen Karyawan | admin | Tambah/ubah/hapus akun |

Aturan penting:

- **Stok tidak pernah diubah langsung** dari form produk. Semua perubahan lewat
  penyesuaian, penambahan, atau transaksi — dan setiap perubahan tercatat di
  tabel `stok_mutasis` beserta alasannya.
- **Transaksi yang dibatalkan tidak dihapus.** Nota ditandai batal, stok
  dikembalikan, dan pelakunya dicatat.
- **Harga diambil dari database**, bukan dari kiriman browser, sehingga harga
  tidak bisa diubah lewat request.

---

## Test

```bash
make test
```

Test berjalan di database `warungku_test` di dalam container dev. Database dev
dan live tidak tersentuh.

---

## Lisensi

MIT.
