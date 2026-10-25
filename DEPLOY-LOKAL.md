# Panduan Deploy Lokal Warungku

Untuk teman satu tim. Aplikasi Laravel + Docker, jalan di laptop masing-masing tanpa perlu PHP/MySQL terpasang di host.

## 1. Siapkan yang dibutuhkan

1. **Docker Desktop / Docker Engine + Compose** — satu-satunya prasyarat. Cek: `docker --version` dan `docker compose version`.
2. Ekstrak paket `warungku-share.7z` ini ke folder mana saja, misal `~/Documents/warungku`.

> 7-Zip: Windows → pakai aplikasi 7-Zip (7-zip.org); Linux → `7z x warungku-share.7z` (paket `p7zip-full`); macOS → `brew install sevenzip`.

## 2. Jalankan — cara gampang: double-klik

- **Windows:** double-klik `setup-windows.bat`
- **Linux/macOS:** jalankan `./setup-linux.sh` (atau double-klik di file manager yang support "Run")

Script-nya otomatis: cek Docker → nyalakan stack → tunggu siap → buka browser di http://127.0.0.1:8010. Kalau Docker belum terpasang, script menunjukkan link installer lalu berhenti — pasang dulu, jalankan lagi.

**Cara manual** (kalau mau):

```bash
docker compose up -d
```

Tunggu 1–2 menit (pertama kali: build image + migrasi + seed otomatis). Lalu buka:

- **Aplikasi:** http://127.0.0.1:8010
- **MySQL (kalau perlu):** `127.0.0.1:3309` — db `warungku_dev`, user `warungku` / `secret` (root: `rootsecret`)

### B. Tanpa Docker (Linux) — script host

`./setup-host-linux.sh` (butuh sudo): pasang PHP 8.x + ekstensi + MariaDB + Composer, buat database `warungku_host` (user `warungku`/`secret`), migrate + seed, lalu `php artisan serve` di **http://127.0.0.1:8010**. Cocok untuk Debian/Ubuntu & turunannya.

### C. Tanpa Docker (Windows) — Laragon

Windows tidak punya script otomatis (instalasi PHP+MySQL native di Windows ribet diotomasi). Jalur praktisnya **Laragon**:

1. Download Laragon Full: https://laragon.org/download
2. Install → Laragon otomatis menyediakan PHP 8.x + MySQL + Composer.
3. Klik **Start All**, lalu taruh folder project ini di `C:\laragon\www\warungku`.
4. Buka **Terminal** Laragon di folder itu, jalankan:
   ```
   composer install
   copy .env.example .env   (lalu isi DB_DATABASE=warungku, DB_USERNAME=root, DB_PASSWORD kosong)
   php artisan key:generate
   php artisan migrate --seed
   ```
5. Laragon → klik nama project `warungku` → browser terbuka.

## 3. Perintah harian (jalur Docker)

> Jalur host (B/C): matikan cukup Ctrl+C di terminal `artisan serve`; perintah di bawah ganti `docker compose exec app php artisan ...` → `php artisan ...`.

```bash
docker compose up -d        # nyalakan
docker compose down         # matikan (data tetap tersimpan di volume)
docker compose down -v      # matikan + hapus data (mulai dari nol)
docker compose exec app php artisan migrate   # jalankan migrasi manual
docker compose logs -f app  # lihat log
```

## 4. Catatan penting

- Kode di-mount dari folder host: edit file langsung terlihat di browser, tidak perlu rebuild.
- Port 8010 (app) dan 3309 (MySQL) sengaja tidak standar supaya tidak bentrok dengan project lain.
- Sudah ada layanan **backup otomatis** harian ke folder `backups/` (retensi 14 hari).
- Kredensial di sini hanya untuk dev, jangan dipakai untuk server live.
- Setiap kali dapat file baru dari repo tim, tinggal timpa file yang berubah — tidak perlu setup ulang.
