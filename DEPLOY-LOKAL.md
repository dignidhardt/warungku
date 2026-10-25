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

## 3. Perintah harian

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
