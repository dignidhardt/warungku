<?php

/**
 * Helper tampilan untuk format rupiah.
 *
 * Sebelumnya setiap view menulis `number_format($n, 0, ',', '.')` sendiri, dan
 * itu mudah terlewat saat menambah halaman baru. Dikumpulkan di satu tempat
 * supaya format uang konsisten di seluruh aplikasi (termasuk pesan flash di
 * controller, yang tidak punya akses ke view).
 */
if (! function_exists('uang')) {
    /**
     * Rp 1.250.000 — tanpa desimal, karena rupiah tidak memakai sen.
     */
    function uang(int|float|null $nilai): string
    {
        return 'Rp '.number_format((int) round($nilai ?? 0), 0, ',', '.');
    }
}

if (! function_exists('uang_tanpa_prefix')) {
    function uang_tanpa_prefix(int|float|null $nilai): string
    {
        return number_format((int) round($nilai ?? 0), 0, ',', '.');
    }
}
