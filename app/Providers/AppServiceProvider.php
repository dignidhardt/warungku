<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Paksa skema https HANYA kalau diminta eksplisit lewat env FORCE_HTTPS.
        //
        // Jangan kunci ke APP_ENV=production: live bisa dilayani lewat http
        // (mis. di belakang reverse proxy yang menerminasi TLS, atau saat
        // diakses lewat IP lokal). Memaksa https di situ membuat semua URL
        // absolut menunjuk ke skema yang tidak dilayani server.
        //
        // Dibaca lewat config(), bukan env(): `env()` mengembalikan null kalau
        // konfigurasi sudah di-cache (`php artisan config:cache`).
        if (filter_var(config('warungku.force_https'), FILTER_VALIDATE_BOOL)) {
            URL::forceScheme('https');
        }
    }
}
