<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Beranda') — {{ config('warungku.nama_toko') }}</title>
    <link rel="stylesheet" href="{{ asset('css/warungku.css') }}">
    @stack('gaya')
</head>
<body>

<header class="navbar">
    <button type="button" class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('tutup')" aria-label="Buka/tutup menu">☰</button>
    <h1>{{ config('warungku.nama_toko') }}</h1>

    <div class="nav-kanan">
        @auth
            <span>{{ auth()->user()->name }}</span>
            <span class="lencana {{ auth()->user()->isAdmin() ? 'biru' : 'abu' }}">{{ auth()->user()->role }}</span>
        @endauth
    </div>
</header>

<div class="kerangka">
    <nav class="sidebar" id="sidebar">
        @php
            $menu = [
                ['route' => 'home', 'label' => 'Beranda'],
                ['route' => 'transaksi.index', 'label' => 'Kasir'],
                ['route' => 'transaksi.riwayat', 'label' => 'Riwayat Transaksi'],
                ['route' => 'stok.index', 'label' => 'Produk & Stok'],
                ['route' => 'laporan.index', 'label' => 'Laporan'],
            ];
        @endphp

        @foreach ($menu as $item)
            <a href="{{ route($item['route']) }}"
               class="{{ request()->routeIs($item['route']) ? 'aktif' : '' }}">
                {{ $item['label'] }}
            </a>
        @endforeach

        @if (auth()->user()->isAdmin())
            <div class="pemisah"></div>
            <div class="label">Administrasi</div>
            <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'aktif' : '' }}">Manajemen Karyawan</a>
        @endif

        <div class="pemisah"></div>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="keluar">Keluar</button>
        </form>
    </nav>

    <main class="isi">
        {{-- Notifikasi global: sukses, error, dan kesalahan validasi ringkas. --}}
        @if (session('success'))
            <div class="pesan sukses">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="pesan galat">{{ session('error') }}</div>
        @endif

        @if (session('info'))
            <div class="pesan info">{{ session('info') }}</div>
        @endif

        @if ($errors->any())
            <div class="pesan galat">
                <strong>Periksa kembali isian berikut:</strong>
                <ul style="margin:8px 0 0 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('konten')
    </main>
</div>

@stack('skrip')
</body>
</html>
