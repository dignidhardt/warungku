<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — {{ config('warungku.nama_toko') }}</title>
    <link rel="stylesheet" href="{{ asset('css/warungku.css') }}">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0d6efd 0%, #0b3f8f 100%);
            padding: 20px;
        }
        .kotak-login {
            width: 100%;
            max-width: 400px;
            background: #fff;
            padding: 36px 32px;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, .25);
        }
        .kotak-login h1 {
            font-size: 21px;
            color: #0f172a;
            text-align: center;
            margin-bottom: 4px;
        }
        .kotak-login p.sub {
            text-align: center;
            color: var(--abu);
            font-size: 13px;
            margin-bottom: 24px;
        }
        .kotak-login .masuk {
            width: 100%;
            height: 44px;
            margin-top: 6px;
        }
        .ingat {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            font-size: 13px;
            color: #475569;
        }
        .ingat input { width: 16px; height: 16px; }
        .info-akun {
            margin-top: 22px;
            padding-top: 16px;
            border-top: 1px solid var(--garis);
            font-size: 12px;
            color: var(--abu);
            line-height: 1.7;
        }
        .info-akun code {
            background: #f1f5f9;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 11.5px;
        }
    </style>
</head>
<body>
    <div class="kotak-login">
        <h1>{{ config('warungku.nama_toko') }}</h1>
        <p class="sub">Masuk untuk mulai mencatat penjualan</p>

        {{-- Kesalahan validasi maupun kredensial salah tampil di sini. --}}
        @if ($errors->any())
            <div class="pesan galat">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.attempt') }}" method="POST">
            @csrf

            <div class="bidang">
                <label for="identifier">Username atau Email</label>
                <input type="text"
                       id="identifier"
                       name="identifier"
                       value="{{ old('identifier') }}"
                       required
                       autofocus
                       autocomplete="username"
                       placeholder="admin">
            </div>

            <div class="bidang">
                <label for="password">Kata Sandi</label>
                <input type="password"
                       id="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       placeholder="••••••••">
            </div>

            <label class="ingat">
                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                Ingat saya di perangkat ini
            </label>

            <button type="submit" class="tombol blok masuk">Masuk</button>
        </form>

        @if (app()->environment('local'))
            <div class="info-akun">
                Akun contoh (development):<br>
                Admin: <code>admin</code> / <code>admin12345</code><br>
                Kasir: <code>kasir</code> / <code>kasir12345</code>
            </div>
        @endif
    </div>
</body>
</html>
