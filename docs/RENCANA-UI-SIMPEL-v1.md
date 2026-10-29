# RENCANA-UI-SIMPEL-v1

**Status: Rencana — belum ada perubahan kode.**

Restyle UI Warungku menjadi sederhana/plain mengikuti visual **Invoizy versi
Flutter**, dengan palet minim: **hitam, putih, toska**. Kerangka tetap
"ala Laravel": **sidebar di kiri**. Fungsionalitas 100% dipertahankan —
tidak ada route, controller, service, model, dan migrasi yang diubah.

## Keputusan (sudah disepakati pemilik)

1. **Restyle, bukan rebuild framework** — tetap Blade + satu CSS custom,
   TIDAK pindah ke Filament.
2. Referensi visual: **Invoizy Flutter** (utama) + web admin Invoizy
   (Filament). Token warna diambil dari `invoizy_theme.dart`.
3. **Desktop/komputer dulu**, HP sekunder (sidebar slide-in tetap ada,
   tabel memakai scroll horizontal).
4. Palet minim: hitam (teks), putih (permukaan), **toska** (aksen):
   - aksen utama: `#0D9D8D` (toska), gelapnya `#0B7E72`
   - latar halaman: `#F7FAF9`, tint permukaan: `#E6F6F3`
   - teks hitam `#111827`, garis/tips abu `#E2E8F0`
   - **Biru (`--biru`) dan kuning (`--kuning`) dihapus** dari seluruh UI.
   - Merah tetap hanya untuk hal fungsional (pesan error, tombol hapus)
     — bukan dekorasi.

## Prinsip eksekusi

- **Warna diubah lewat `public/css/warungku.css` saja** (nama class di view
  dipertahankan supaya controller/view tidak perlu disentuh), plus koreksi
  kecil di view untuk `style=` inline yang berwarna mencolok.
- Hal penting tetap utuh tanpa menurunkan fitur: helper `uang()`, `Rupiah`
  di `rupiah.js`, `.lebar-tetap` (1200px), lencana peran, guard test, dan
  seluruh pola mutasi stok.
- Eksekusi kode didelegasikan ke harness Opencode CLI sesuai kebiasaan
  pemilik; verifikasi browser dan test tetap dilakukan setelahnya.

## Fase

### Fase 0 — Persiapan

- [ ] Cabang baru `ui-sederhana` dari `feat/docker-dan-perbaikan`.
- [ ] Screenshot baseline semua halaman dev (login, beranda, kasir, riwayat,
      produk & stok, detail produk, pembelian, supplier, laporan, tutup
      buku, keuangan ×4, users, master data ×4) untuk pembanding.
- [ ] Grep sisa warna lama: `--biru`, `--kuning`, `#0d6efd`, `#ffc107`,
      dan `style=` inline pada 34 view → daftar file yang disentuh.

### Fase 1 — Kerangka & tema (efek terbesar)

- [ ] `warungku.css`: ganti token warna di `:root`, hapus biru/kuning.
- [ ] Navbar toska (setara AppBar Invoizy), teks putih; lencana peran:
      admin = toska, gudang = hijau redup, kasir = abu.
- [ ] Sidebar putih; item aktif = teks toska + tint `#E6F6F3` (setara
      indikator NavigationBar Invoizy), bukan blok warna pekat.
- [ ] Kartu: radius 16px ala Invoizy, bayang sangat lembut atau garis saja.
- [ ] Tombol utama toska radius 12px; tombol sekunder outline abu; tombol
      hapus tetap merah.
- [ ] Pesan sukses/error/info netral: sukses = tint toska, error = merah.

### Fase 2 — Pembersihan per halaman (desktop dulu)

- [ ] Beranda: kartu ringkasan flat, angka toska, tanpa blok warna per kartu.
- [ ] Kasir: saran pencarian, keranjang, input uang terformat — tampilan
      saja; perilaku `rupiah.js` tidak disentuh.
- [ ] Laporan dan halaman export (excel/pdf cukup warna header tabel saja).
- [ ] Tutup buku, keuangan (ringkasan, laba rugi, buku kas, pengeluaran),
      pembelian, supplier, users, master data ×3, form & login.
- [ ] Ganti semua `style="...color..."` inline mencolok di view dengan class.

### Fase 3 — HP sekunder

- [ ] Sidebar slide-in di bawah 721px tetap ada, hanya restyled.
- [ ] Tabel sempit memakai scroll horizontal, terutama laporan dan kasir.

### Fase 4 — Verifikasi & serah

- [ ] `make up` dev → cek semua halaman via browser: screenshot, cek
      overflow horizontal, cek konsol tanpa error.
- [ ] `make test` (43 fitur) — semua harus hijau tanpa perubahan kode PHP.
- [ ] `bash scripts/uji-keuangan.sh` alur HTTP end-to-end.
- [ ] Pastikan warna lama habis: `grep -rn "0d6efd|ffc107|--biru|--kuning"`.
- [ ] Commit + entri CHANGELOG (Belum Dirilis → Diubah).

## Batasan (non-goals)

- Tidak mengubah route, middleware, controller, service, test, model, dan
  basis data.
- Tidak menambah/menghapus fitur atau menu.
- Halaman struk & export pdf hanya penyesuaian warna, format cetak tidak.

## Risiko yang diawasi

- Beberapa view memakai `style=` inline — dicari semua lalu diganti class.
- Halaman export excel/pdf punya warna header sendiri — dijaga sederhana
  juga.
- Sidebar mobile (di bawah 721px) sensitif CSS; uji di viewport 360px.
- Kasir merender elemen (saran produk, keranjang) via class tertentu —
  semua class yang dihasilkan JS juga ikut direstyling.
