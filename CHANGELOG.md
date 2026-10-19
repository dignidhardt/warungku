# Changelog

Semua perubahan penting pada proyek ini dicatat di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
dan proyek ini memakai [Semantic Versioning](https://semver.org/lang/id/).

## [Belum Dirilis]

### Ditambahkan

- **Manajemen keuangan**: pencatatan pembelian ke supplier, pengeluaran
  operasional (listrik, transport, dll), dan prive (pengambilan uang pribadi
  pemilik) yang dipisah dari biaya usaha.
- **Laporan laba rugi**: omzet dikurangi harga pokok penjualan dan biaya
  operasional, sehingga pemilik melihat laba bersih, bukan hanya laba kotor.
- **Tutup buku harian (cash opname)**: kasir menghitung uang fisik di akhir
  hari, sistem mencatat selisih kas beserta alasannya.
- **Diskon** per nota dan **metode pembayaran** (tunai, QRIS, transfer, debit).
- **Retur penjualan**: mengembalikan sebagian barang dari satu nota tanpa
  membatalkan seluruh transaksi.
- **Multi-satuan** produk (pcs, dus, kg, liter) untuk kebutuhan sembako.
- **Pencarian produk di kasir** yang menampilkan saran saat mengetik, tidak
  lagi bergantung pada `datalist` bawaan browser.
- **Backup otomatis terjadwal** lewat cron container.
- Halaman **Buku Kas** untuk melihat seluruh arus uang masuk dan keluar.
- **CHANGELOG.md** ini.

### Diubah

- `composer.json` memakai nama dan deskripsi proyek yang sebenarnya, bukan
  lagi metadata skeleton Laravel.
- Seeder mencatat stok awal sebagai mutasi stok, bukan menulis kolom langsung,
  agar aturan "setiap perubahan stok punya jejak" berlaku juga untuk data contoh.
- Beranda menampilkan ringkasan keuangan bulanan (omzet bersih, laba kotor,
  biaya operasional, laba bersih), nilai persediaan, kas yang seharusnya ada
  hari ini, dan saran pembelian untuk barang yang menipis.
- Navigasi sidebar ditambah menu Keuangan (Ringkasan, Laba Rugi, Buku Kas,
  Pengeluaran, Pembelian) dan Tutup Buku.
- Tutup buku boleh dibuka kasir karena itu pekerjaan akhir shift, bukan
  wewenang pemilik. Laba rugi dan buku kas tetap khusus admin.

### Diperbaiki

- Nomor nota memakai komponen acak sehingga dua transaksi pada detik yang sama
  tidak lagi gagal karena unique index.
- Penyimpanan transaksi dibungkus database transaction dengan penguncian baris,
  sehingga stok tidak bisa minus saat dua kasir menjual bersamaan.
- Harga selalu diambil dari database, bukan dari kiriman browser.
- Login menerima username maupun email, dan pesan kesalahan benar-benar tampil.
- Migrasi `transaksis` dibuat idempoten: MySQL tidak bisa me-rollback DDL, jadi
  migrasi yang gagal di tengah dulu meninggalkan kolom setengah jadi dan tidak
  bisa dijalankan ulang selamanya.
- Rute `/stok/create` didaftarkan sebelum `/stok/{stok}` agar tidak dianggap id
  produk.
- `make backup` memakai `--no-tablespaces` sehingga tidak lagi mencetak
  "Access denied ... PROCESS" sambil menghasilkan file yang tampak berhasil.
- Kolom `kas_seharusnya` pada `tutup_bukus` diubah dari unsigned ke signed.
  Nilai kas bisa negatif (pengeluaran lebih besar dari penjualan), dan versi
  lama menolaknya dengan "Out of range value" tepat di hari yang paling perlu
  dicatat.
- Seeder tidak lagi menimpa kolom `stok` produk yang sudah ada. Sebelumnya
  setiap `db:seed` mengembalikan stok ke 0 dan memusnahkan penjualan yang
  sudah tercatat; sekarang aman dijalankan berulang.
- Tutup buku menolak disimpan tanpa alasan kalau uang fisik tidak sama
  dengan yang seharusnya. Selisih tanpa penjelasan tidak bisa ditelusuri.

### Keamanan

- 26 kerentanan pada 8 paket dependency diperbaiki (Laravel, Guzzle,
  CommonMark, Flysystem, PHPSpreadsheet, Symfony). `composer audit` kini bersih.

## [1.0.0] - 2026-10-19

Versi pertama yang bisa dijalankan: aplikasi kasir untuk warung dengan
pengelolaan produk dan stok, pencatatan penjualan tunai, cetak struk, dan
laporan penjualan.

### Ditambahkan

- Docker Compose terpisah untuk development (`:8010`, database `warungku_dev`)
  dan live (`:8011`, database `warungku`, MySQL tidak dipublikasi ke host).
- Dockerfile multi-stage: kode di-mount saat dev, disalin ke image saat live.
- Database test terpisah (`warungku_test`) dengan pengaman di `tests/TestCase.php`
  yang menolak berjalan bila nama database bukan database test.
- `Makefile` untuk operasi harian: `up`, `test`, `fresh`, `backup`, `restore`,
  `live-*`.
- Migrasi tabel `stok` (sebelumnya tidak ada, aplikasi tidak bisa dijalankan).
- Tabel `stok_mutasis`: riwayat setiap perubahan stok beserta alasannya.
- Kolom audit pada `transaksis`: `user_id`, `dibatalkan_pada`, `dibatalkan_oleh`.
- Halaman Kasir dengan keranjang, validasi stok, dan tombol uang pas.
- Halaman Struk yang bisa dicetak (terpisah dari layout, hasil cetak bersih).
- Halaman Riwayat Transaksi dengan filter tanggal dan nomor nota.
- Pembatalan transaksi oleh admin: stok dikembalikan, nota ditandai batal,
  pelaku dicatat. Riwayat tidak pernah dihapus.
- Tambah stok (barang datang) dan penyesuaian stok (stok opname) dengan alasan
  wajib.
- Laporan penjualan: omzet, laba kotor, produk terlaris, export Excel dan PDF.
- Manajemen karyawan lengkap: tambah, ubah, hapus, ganti sandi, jaga agar
  minimal satu admin tersisa.
- Peringatan stok menipis dan stok habis di beranda.
- Rate limit login (5 percobaan per menit).
- 43 test fitur (158 assertion) yang mengunci perilaku kritis.
- `README.md` dengan instruksi instalasi, peta environment, dan peta aplikasi.

### Diubah

- CSS dipindah dari inline di setiap view menjadi `public/css/warungku.css`.
- Struktur view dirapikan per modul (`stok/`, `transaksi/`, `laporan/`, `users/`).

### Dihapus

- Template Mega Able 13 MB di `public/assets/` (nol referensi dari view mana pun).
- `public/index.html` (halaman demo template), `welcome.blade.php` bawaan Laravel.
- `KasirController` (menunjuk view yang sudah dihapus), model `Produk` (duplikat
  `Stok` dan tidak dipakai).
- `package.json`, `vite.config.js`, `resources/css`, `resources/js`: proyek tidak
  memakai Node maupun Tailwind.

### Diperbaiki

- `User::$fillable` tidak memuat `username` dan `role`, sehingga kedua kolom
  dibuang diam-diam saat membuat akun.
- `DatabaseSeeder` gagal total karena `username` NOT NULL tanpa default.
- `UserController` tidak punya method `edit()`/`update()` padahal route-nya
  terdaftar.
- `Route::resource('transaksi')` mendaftarkan 7 route padahal controller hanya
  punya 2 method.
- `detail_produk` ter-encode dua kali (kolom string + cast array + json_encode).
- `IsAdmin.php` punya blok `namespace` dan `use` ganda di tengah file.
- `LaporanController` memakai `YEAR()` yang hanya ada di MySQL.
- Flash message di `transaksi.blade.php` ditaruh di luar `@section` sehingga
  tidak pernah dirender.

[Belum Dirilis]: https://example.com/compare/v1.0.0...HEAD
[1.0.0]: https://example.com/releases/tag/v1.0.0
