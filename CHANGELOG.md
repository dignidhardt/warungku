# Changelog

Semua perubahan penting pada proyek ini dicatat di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
dan proyek ini memakai [Semantic Versioning](https://semver.org/lang/id/).

## [Belum Dirilis]

### Ditambahkan

- **Hak akses "Gudang"** (khusus admin yang membuat akunnya): read/write
  Produk & Stok, Pembelian, dan Supplier — sama seperti admin untuk
  menu-menu itu. Aksesnya: Beranda (read), Produk & Stok (read/write),
  Pembelian (read/write), dan Supplier (read/write). Menu yang tampil
  hanya empat itu; keuangan, retur, master data, dan karyawan tetap
  khusus admin.
- **Halaman master data** (khusus admin) untuk mengelola pilihan yang
  sebelumnya ditulis bebas sebagai teks di form produk:
  - **Kategori** — punya atribut "Kode Barang" 3 karakter (mis. "AAA").
  - **Satuan** — satuan dasar produk (pcs, kg, liter, dst).
  - **Satuan Kemasan** — satuan kemasan (karung, dus, renceng).
  - **Supplier** — nama, kode (auto "SUP-001"), telepon, alamat.
  Form Tambah Produk sekarang memakai dropdown dari halaman-halaman ini,
  bukan input string custom. Master data lama sudah ditautkan otomatis
  ke produk dan pembelian yang sudah tercatat.
- **Kode Barang produk terisi otomatis** dari Kode Barang kategorinya:
  kategori "AAA" memberi "AAA-001", "AAA-002", dan seterusnya. Admin tetap
  boleh mengubahnya manual.
- **Supplier otomatis di form Pembelian**: tiap baris barang menampilkan
  supplier dari produk yang dipilih. Satu pembelian boleh berisi barang
  dari beberapa supplier berbeda.
- **Riwayat Stok** di halaman Produk & Stok: container baru berisi seluruh
  catatan "Stok Masuk" dan "Stok Keluar" beserta produknya, jumlah, stok
  sesudahnya, keterangan, dan siapa yang melakukan. Paginasi riwayat
  terpisah dari daftar produk (`pageMutasi` vs `pageProduk`) supaya
  menggulir riwayat tidak menggeser halaman daftar produk.
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
- Perintah `make bersih` untuk mengosongkan data operasional (akun tetap ada)
  supaya alur bisa diuji dari nol tanpa menunggu `migrate:fresh`.
- **CHANGELOG.md** ini.

### Diubah

- **Format rupiah seragam di seluruh aplikasi**: "Rp 100.000,00" (dengan
  desimal, dua angka di belakang koma). Sebelumnya formatnya campuran —
  sebagian "Rp 100.000", sebagian "Rp 100.000,00". Semua tampilan kini
  memakai helper `uang()` yang sama.
- **Input nominal rupiah terformat otomatis**: field "Uang Diterima" dan
  "Diskon" di Kasir, harga beli/jual di form produk, harga beli di
  form Pembelian, dan "Uang Fisik" di Tutup Buku. Saat diketik muncul
  pemisah ribuan ("100.000"), saat selesai mengetik berubah jadi
  "Rp 100.000,00". Angka yang dikirim ke server tetap angka polos.
- **Nama master data memakai huruf awal besar**: "dus" tersimpan jadi
  "Dus", "botol" jadi "Botol", "toko adi" jadi "Toko Adi".
- **Halaman Detail Produk jadi read-only**: container "Tambah Stok (Barang
  Datang)" dan "Penyesuaian Stok (Stok Opname)" dihapus, supaya tidak ada
  dua jalan untuk mengubah stok yang bisa saling bertentangan. Informasi
  kategori, supplier, satuan kemasan, dan batas stok menipis ditambahkan.
- **Sidebar**: posisi "Pengeluaran" dan "Pembelian" ditukar. Menu untuk
  peran Gudang ditampilkan terpisah di sesi "Gudang".
- **Kode Barang** menggantikan nama "Barcode" di form produk. Nilainya
  terisi otomatis dari Kode Barang kategori ("AAA-001") dan bisa diubah
  manual oleh admin.
- **Halaman Kasir**: field "Pilih Produk" dipindah ke card sendiri di
  ATAS card "Keranjang", dan labelnya diubah dari "Cari nama atau barcode"
  menjadi "Pilih Produk". Deskripsi "Pilih produk untuk melihat sisa
  stok" dihapus. Tombol nominal cepat (Uang pas, 5.000, 10.000, 50.000,
  100.000) dihapus dari form pembayaran.
- **Halaman Pembelian**: container "Info Pembelian" dihapus. Isinya
  (Tanggal & Keterangan) pindah ke card tersendiri, dan Supplier jadi
  kolom otomatis per baris barang.
- **Sidebar**: posisi "Riwayat Transaksi" dan "Produk & Stok" ditukar.
  Menu Kategori, Satuan, Satuan Kemasan, dan Supplier ditambahkan di bawah
  "Manajemen Karyawan" (khusus admin).
- `make bersih` sekarang mempertahankan master data (kategori, satuan,
  satuan kemasan, supplier) — bukan hanya akun. Master data adalah
  konfigurasi, bukan data operasional: menghapusnya membuat form produk
  tidak punya pilihan sama sekali.
- **Lebar konten seragam di semua halaman utama** (Kasir, Riwayat Transaksi,
  Produk & Stok, Laporan, Tutup Buku): seluruh isi dibungkus satu wadah
  `.lebar-tetap` dengan lebar maksimum 1200px dan dipusatkan. Sebelumnya
  konten melebar mengikuti ukuran layar, sehingga di monitor besar muncul
  ruang kosong yang lebar di sisi kanan halaman.
- **Halaman Kasir jadi satu kolom**: container "Pilih Produk" dihapus dan
  input pencarian produk + jumlah dipindahkan ke dalam baris `<tfoot>` tabel
  keranjang, tepat di bawah daftar "PRODUK". Satu baris, satu kartu.
- **Halaman Produk & Stok dibalut dalam satu container "Stok"**: ringkasan
  stok habis/menipis, filter, dan tabel produk kini berada dalam satu
  kartu, bukan tersebar di beberapa container terpisah.
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

- **Halaman Edit Produk 500 (TypeError)**. Relasi `kategori()` pada model
  Stok bertabrakan dengan kolom teks `kategori`: Eloquent mencari atribut
  lebih dulu, jadi `$produk->kategori` mengembalikan string, bukan objek
  relasi — dan `kodeBarangOtomatis()` yang menerima `?Kategori` melempar
  TypeError. Relasinya sekarang bernama `kategoriRelasi()`.
- **Pagar pengaman test bisa menghapus data dev/live**. `RefreshDatabase`
  menjalankan `migrate:fresh` (menghapus SEMUA tabel) pada database yang
  belum di-migrate, dan guard yang menolak test non-test baru dijalankan
  SETELAH itu. Satu perintah `php artisan test` tanpa
  `DB_DATABASE=warungku_test` bisa jadi menghapus seluruh data dev.
  Sekarang guard dicek lebih dulu, sebelum database disentuh sama sekali.
- **Menu navigasi menutupi isi halaman di layar ponsel**. Sidebar di bawah
  721px menjadi menu slide-in, tapi tidak pernah disembunyikan secara default,
  jadi ~70% halaman tertutup dan tidak bisa dibaca sampai tombol hamburger
  ditekan. Sekarang menu tertutup secara default, muncul dengan tirai gelap
  di belakangnya, dan tertutup lagi saat menu dipilih atau tirai diklik.
- **Daftar saran produk di kasir terpotong** setelah input produk dipindah
  ke dalam tabel keranjang. Daftar saran memakai `position: fixed` dengan
  posisi dihitung dari kotak layar input, jadi tetap menempel di bawah
  input meski halaman digulir.
- Halaman **Kasir 500** karena directive Blade salah sintaks. Daftar produk
  dirakit langsung di view dengan `@json($produk->map(fn ($p) => [...]))`;
  Blade mengurai `=>` dan `[` sebagai directive-nya sendiri sehingga hasilnya
  "Unclosed '[' on line 230". Sekarang logikanya dipindah ke controller dan
  view hanya menerima variabel jadi.
- Blade juga mem-proses directive yang hanya **tertulis di dalam komentar**
  pada blok `<script>`. Catatan penjelasan dipindahkan ke luar blok script.
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
