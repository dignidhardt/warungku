#!/bin/sh
# Entrypoint container Warungku (dipakai dev dan live).
# Tugas: pastikan dependency PHP ada dan .env terpasang, lalu serahkan ke CMD.
set -e

cd /var/www/html

if [ ! -f .env ]; then
    echo "[entrypoint] FATAL: .env tidak ada di dalam container."
    echo "[entrypoint] Dev : pastikan mount .env.docker -> .env ada di compose.yaml"
    echo "[entrypoint] Live: jalankan 'make live-env' atau salin .env.live.example ke .env.live"
    exit 1
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    echo "[entrypoint] FATAL: APP_KEY kosong di .env."
    echo "[entrypoint] Isi dengan: php artisan key:generate --show (lalu salin hasilnya)"
    exit 1
fi

# Clone baru belum punya vendor/ (dev memakai bind mount, image live sudah
# menyalin vendor). Install sekali di sini supaya `docker compose up` cukup
# satu perintah tanpa langkah manual.
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ belum ada, menjalankan composer install ..."
    composer install --no-interaction --prefer-dist --no-progress
fi

# Tunggu database siap (healthcheck compose sudah menangani; ini jaring kedua).
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
    tries=0
    while ! php -r 'exit(@fsockopen(getenv("DB_HOST") ?: "127.0.0.1", (int)(getenv("DB_PORT") ?: 3306)) ? 0 : 1);' 2>/dev/null; do
        tries=$((tries + 1))
        if [ "$tries" -ge 30 ]; then
            echo "[entrypoint] database belum bisa dihubungi setelah 30 detik, lanjut saja."
            break
        fi
        sleep 1
    done
fi

# Migrasi otomatis HANYA untuk development. Live selalu manual.
if [ "${APP_ENV:-local}" = "local" ] && [ "${AUTO_MIGRATE:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction || true

    # Seed otomatis SEKALI: hanya kalau tabel users masih kosong. Ini yang
    # membuat instalasi baru langsung punya akun admin, tanpa menimpa data
    # kalau container di-restart berkali-kali.
    if [ "${AUTO_SEED:-true}" = "true" ]; then
        jumlah_user=$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tr -dc '0-9')
        if [ "${jumlah_user:-0}" = "0" ]; then
            echo "[entrypoint] database dev kosong, menjalankan seeder awal ..."
            php artisan db:seed --force --no-interaction || true
        fi
    fi
fi

exec "$@"
