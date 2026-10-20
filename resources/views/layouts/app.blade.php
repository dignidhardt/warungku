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
    <button type="button" class="menu-toggle" id="menu-toggle" aria-label="Buka/tutup menu" aria-expanded="false">☰</button>
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
                ['route' => 'keuangan.tutup-buku', 'label' => 'Tutup Buku'],
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
            <div class="label">Keuangan</div>
            <a href="{{ route('keuangan.index') }}" class="{{ request()->routeIs('keuangan.*') ? 'aktif' : '' }}">Ringkasan Keuangan</a>
            <a href="{{ route('keuangan.laba-rugi') }}" class="{{ request()->routeIs('keuangan.laba-rugi') ? 'aktif' : '' }}">Laba Rugi</a>
            <a href="{{ route('keuangan.buku-kas') }}" class="{{ request()->routeIs('keuangan.buku-kas') ? 'aktif' : '' }}">Buku Kas</a>
            <a href="{{ route('keuangan.pengeluaran') }}" class="{{ request()->routeIs('keuangan.pengeluaran*') ? 'aktif' : '' }}">Pengeluaran</a>
            <a href="{{ route('pembelian.index') }}" class="{{ request()->routeIs('pembelian.*') ? 'aktif' : '' }}">Pembelian</a>

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

    {{-- Tirai gelap di belakang menu pada layar kecil. Hanya muncul
         saat menu dibuka;内容量 bisa diklik untuk menutupnya. --}}
    <div class="tirai-sidebar" id="tirai-sidebar" hidden></div>

    <main class="isi">
        {{-- Semua isi halaman dibungkus satu wadah lebar tetap supaya
             tiap menu punya lebar yang sama: tidak melar mengikuti ukuran
             layar, dan tidak jadi kolom pendek di layar besar. --}}
        <div class="lebar-tetap">
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
        </div>
    </main>
</div>

@stack('skrip')
<script>
/*
 * Menu navigasi di layar kecil.
 *
 * Di layar kecil sidebar jadi menu slide-in. Ia harus TERTUTUP secara
 * default: kalau terbuka, menu menutupi sebagian besar isi halaman dan
 * pengguna tidak bisa membaca halaman sampai menekan tombol hamburger.
 *
 * Di layar lebar sidebar selalu terlihat, jadi JavaScript ini tidak
 * melakukan apa-apa (kelas .terbuka/.tutup hanya influential di bawah
 * 721px lewat CSS).
 */
(function () {
    'use strict';

    const sidebar = document.getElementById('sidebar');
    const tombol = document.getElementById('menu-toggle');
    const tirai = document.getElementById('tirai-sidebar');

    if (!sidebar || !tombol) {
        return;
    }

    const layarKecil = function () {
        return window.matchMedia('(max-width: 720px)').matches;
    };

    const setMenu = function (buka) {
        sidebar.classList.toggle('terbuka', buka);
        sidebar.classList.toggle('tutup', !buka);
        tombol.setAttribute('aria-expanded', buka ? 'true' : 'false');

        if (tirai) {
            tirai.hidden = !buka;
        }
    };

    // Mulai tertutup di layar kecil, terbuka di layar lebar.
    setMenu(!layarKecil());

    tombol.addEventListener('click', function () {
        setMenu(!sidebar.classList.contains('terbuka'));
    });

    if (tirai) {
        tirai.addEventListener('click', function () {
            setMenu(false);
        });
    }

    // Memilih menu akan menutupnya (di layar kecil) supaya isi halaman
    // langsung terlihat.
    sidebar.querySelectorAll('a').forEach(function (tautan) {
        tautan.addEventListener('click', function () {
            if (layarKecil()) {
                setMenu(false);
            }
        });
    });

    // Memutar layar atau mengubah ukuran jendela: sesuaikan tampilan menu
    // dengan mode yang sedang aktif.
    window.addEventListener('resize', function () {
        setMenu(!layarKecil());
    });
})();
</script>
</body>
</html>
