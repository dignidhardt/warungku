# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage `base` — PHP + ekstensi + composer. Dipakai bersama oleh dev & live.
# ---------------------------------------------------------------------------
FROM php:8.4-cli AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        ca-certificates \
        curl \
        libpng-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        mbstring \
        intl \
        exif \
        pcntl \
        zip \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]

# ---------------------------------------------------------------------------
# Stage `production` — image mandiri untuk LIVE.
#
# Kode DISALIN ke image (tidak di-mount), sehingga file di host tidak bisa
# mengubah aplikasi yang sedang berjalan. `vendor/` sengaja tidak diambil dari
# host: dependency dipasang di dalam image agar cocok dengan PHP di container.
# ---------------------------------------------------------------------------
FROM base AS production

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --prefer-dist --no-progress \
        --no-scripts --optimize-autoloader \
    && composer clear-cache

COPY . .

# Autoloader & package discovery dijalankan setelah kode tersedia (butuh
# app/ dan routes/). Kegagalan di sini tidak boleh menggagalkan build karena
# sebagian perintah artisan memerlukan APP_KEY yang baru ada saat runtime.
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi || true

# Direktori yang harus bisa ditulis saat runtime (storage di-mount volume).
RUN mkdir -p storage/framework/{cache/data,sessions,views,testing} \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
    && chmod -R 0775 storage bootstrap/cache
