<?php

/**
 * Helper tampilan untuk format rupiah.
 *
 * Format tunggal untuk seluruh aplikasi: "Rp 100.000,00".
 *
 * Kenapa ada desimal padahal rupiah tidak memakai sen:
 * - Format dengan desimal selalu (",00") lebih konsisten dan tidak pernah
 *   berantakan antara tabel yang satu kolomnya "Rp 5.000" dan yang lain
 *   "Rp 5.000,00".
 * - Angka yang sudah berdesimal (dari perhitungan yang bisa pecah) tetap
 *   tampil utuh, tidak dibulatkan diam-diam.
 *
 * Sebelumnya setiap view menulis `number_format($n, 0, ',', '.')` sendiri, dan
 * itu mudah terlewat saat menambah halaman baru. Dikumpulkan di satu tempat
 * supaya format uang konsisten di seluruh aplikasi (termasuk pesan flash di
 * controller, yang tidak punya akses ke view).
 */
if (! function_exists('uang')) {
    /**
     * "Rp 100.000,00"
     *
     * Angka negatif ditulis dengan tanda minus Unicode ("−"), bukan
     * hyphen ("-"), supaya tampilan di tabel rapi dan sama dengan tanda
     * minus yang dipakai manual di beberapa tampilan.
     */
    function uang(int|float|null $nilai): string
    {
        $nilai = (float) ($nilai ?? 0);

        if ($nilai < 0) {
            return '− Rp '.number_format(abs($nilai), 2, ',', '.');
        }

        return 'Rp '.number_format($nilai, 2, ',', '.');
    }
}

if (! function_exists('uang_tanpa_prefix')) {
    /**
     * "100.000,00" — tanpa awalan "Rp".
     */
    function uang_tanpa_prefix(int|float|null $nilai): string
    {
        $nilai = (float) ($nilai ?? 0);

        if ($nilai < 0) {
            return '− '.number_format(abs($nilai), 2, ',', '.');
        }

        return number_format($nilai, 2, ',', '.');
    }
}

if (! function_exists('uang_tanpa_desimal')) {
    /**
     * "100.000" — untuk input form: angka bulat saja, tanpa ",00" yang
     * merepotkan saat diketik ulang.
     */
    function uang_tanpa_desimal(int|float|null $nilai): string
    {
        return number_format((float) ($nilai ?? 0), 0, ',', '.');
    }
}

if (! function_exists('rupiah_input')) {
    /**
     * Format angka input rupiah: "100000" -> "100.000" (pembatas ribuan
     * saja, tanpa desimal supaya tidak merepotkan saat diketik ulang).
     *
     * Dipakai untuk field input supaya nominal yang diketik lebih mudah
     * dibaca. Nilai yang dikirim ke server tetap angka biasa.
     */
    function rupiah_input(int|float|string|null $nilai): string
    {
        $angka = preg_replace('/[^0-9]/', '', (string) $nilai);

        if ($angka === '') {
            return '';
        }

        return number_format((float) $angka, 0, ',', '.');
    }
}

if (! function_exists('huruf_awal_besar')) {
    /**
     * "toko adi" -> "Toko Adi", "dus" -> "Dus", "botol" -> "Botol".
     *
     * Huruf pertama setiap kata dibesarkan. Dipakai untuk master data
     * (kategori, satuan, satuan kemasan, supplier) supaya penamaannya
     * konsisten: tidak ada lagi "dus" di satu tempat dan "Dus" di tempat lain.
     */
    function huruf_awal_besar(string $teks): string
    {
        $teks = trim($teks);

        if ($teks === '') {
            return '';
        }

        // Kata-kata penghubung (dan, dari, untuk, dll) tetap lowercase
        // supaya nama seperti "Toko dan Grosir" tidak jadi "Toko Dan Grosir".
        $kecil = ['dan', 'di', 'dari', 'ke', 'untuk', 'pada', 'yang', 'di'];

        $kata = preg_split('/(\s+)/u', $teks, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($kata as $i => $bagian) {
            if ($bagian === '' || preg_match('/^\s+$/u', $bagian)) {
                continue;
            }

            $kata[$i] = in_array(mb_strtolower($bagian), $kecil, true)
                ? mb_strtolower($bagian)
                : mb_convert_case($bagian, MB_CASE_TITLE, 'UTF-8');
        }

        return implode('', $kata);
    }
}
