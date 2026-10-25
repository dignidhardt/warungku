#!/usr/bin/env bash
# Setup Warungku TANPA Docker — PHP + Composer + MariaDB langsung di host.
# Untuk Debian/Ubuntu & turunannya. Butuh sudo.
set -e
cd "$(dirname "$0")"

# 1. Paket sistem (versi php: coba 8.4, fallback 8.3/8.2)
if ! command -v php >/dev/null 2>&1 || php -r 'exit(version_compare(PHP_VERSION,"8.2",">=")?0:1);'; then
  echo "Memasang PHP 8.x + ekstensi + MariaDB (butuh sudo)..."
  sudo apt-get update
  sudo apt-get install -y ca-certificates curl gnupg
  if sudo add-apt-repository -y ppa:ondrej/php 2>/dev/null; then
    PHP=8.4
  else
    PHP=$(apt-cache search --names-only '^php8\.[0-9]+$' 2>/dev/null | sort -rV | head -1 | awk '{print $1}' | sed 's/php//')
    [ -n "$PHP" ] || { echo "Tidak menemukan PHP 8.x di repo. Untuk Debian: pasang sury.org dulu."; exit 1; }
  fi
  sudo apt-get install -y "php$PHP-cli" "php$PHP-mysql" "php$PHP-mbstring" "php$PHP-xml" \
    "php$PHP-curl" "php$PHP-zip" "php$PHP-gd" "php$PHP-intl" "php$PHP-bcmath" \
    mariadb-server unzip
fi
command -v composer >/dev/null 2>&1 || {
  curl -sS https://getcomposer.org/installer | php -- --install-dir="$HOME/.local/bin" --filename=composer --quiet
  export PATH="$HOME/.local/bin:$PATH"
}

# 2. Database
sudo systemctl enable --now mariadb 2>/dev/null || sudo service mariadb start
sudo mysql -e "CREATE DATABASE IF NOT EXISTS warungku_host CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'warungku'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL ON warungku_host.* TO 'warungku'@'localhost'; FLUSH PRIVILEGES;"

# 3. Laravel
[ -f .env ] || cp .env.example .env
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/; s/^DB_HOST=.*/DB_HOST=127.0.0.1/; s/^DB_PORT=.*/DB_PORT=3306/;
  s/^DB_DATABASE=.*/DB_DATABASE=warungku_host/; s/^DB_USERNAME=.*/DB_USERNAME=warungku/; s/^DB_PASSWORD=.*/DB_PASSWORD=secret/' .env
composer install --no-interaction
[ -f .env ] && grep -q '^APP_KEY=base64' .env || php artisan key:generate

echo "Migrasi + seed..."
php artisan migrate --seed --force

echo "Menyalakan server (Ctrl+C untuk berhenti)..."
URL=http://127.0.0.1:8010
( command -v xdg-open >/dev/null 2>&1 && sleep 3 && xdg-open "$URL" >/dev/null 2>&1 ) &
php artisan serve --host=127.0.0.1 --port=8010
