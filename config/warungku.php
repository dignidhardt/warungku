<?php

return [
    /*
     * Kata sandi admin yang dibuat oleh DatabaseSeeder.
     * Nilai ini hanya untuk instalasi baru; ganti segera setelah login pertama.
     */
    'admin_password' => env('ADMIN_PASSWORD', 'admin12345'),

    /*
     * Nama toko yang tampil di navigasi dan struk.
     */
    'nama_toko' => env('NAMA_TOKO', 'Warungku'),

    'alamat_toko' => env('ALAMAT_TOKO', ''),

    'telepon_toko' => env('TELEPON_TOKO', ''),

    /*
     * Paksa URL absolut memakai https. Aktifkan hanya kalau aplikasi memang
     * dilayani lewat HTTPS; kalau tidak, semua link menunjuk skema yang tidak
     * dilayani server.
     */
    'force_https' => env('FORCE_HTTPS', false),
];
