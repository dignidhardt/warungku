/*
 * Format rupiah untuk input & tampilan.
 *
 * Aturan:
 * - Saat difokuskan: field menampilkan angka polos ("100000") supaya mudah
 *   diedit — kalau langsung "Rp 100.000,00", kursor akan berada di antara
 *   karakter dan mengetik jadi kacau.
 * - Saat diketik: pemisah ribuan muncul otomatis ("100.000").
 * - Saat blur (selesai): format penuh "Rp 100.000,00".
 *
 * PENTING soal implementasi:
 * Angka yang diketik disimpan terpisah dari yang ditampilkan. Kalau hanya
 * mengandalkan `input.value`, setiap ketikan membaca ulang angka yang
 * SUDAH diberi pemisah ribuan lalu menuliskannya balik:
 * "12500" -> "125.000" -> "12500000" (angka berlipat terus).
 *
 * Dua mode:
 * 1. Input tampilan + input tersembunyi (pola di Kasir): nilai ke server
 *    diambil dari input tersembunyi.
 * 2. Input itu sendiri yang jadi nama field (pola di form produk): nilai
 *    dikirim apa adanya, tapi harus berupa angka polos saat form disubmit.
 *
 * Cara pasang:
 *   <input data-rupiah>                          <-- mode 2
 *   <input data-rupiah data-rupiah-asli="bayar"> <-- mode 1 (tampilan)
 *   <input type="hidden" data-rupiah-tujuan="bayar">
 */
(function (global) {
    'use strict';

    /* Ubah angka jadi "Rp 100.000,00". */
    const format = function (nilai, denganPrefix) {
        const n = Math.round((Number(nilai) || 0) * 100) / 100;
        const absolut = Math.abs(n).toFixed(2).replace('.', ',');
        const ribuan = absolut.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        const prefix = denganPrefix === false ? '' : 'Rp ';

        return (n < 0 ? '− ' : '') + prefix + ribuan;
    };

    /* Ambil angka dari string berformat: "Rp 100.000,00" -> 100000 */
    const keAngka = function (nilai) {
        if (typeof nilai === 'number') {
            return nilai;
        }

        const bersih = String(nilai == null ? '' : nilai).replace(/[^0-9]/g, '');

        return bersih === '' ? 0 : parseInt(bersih, 10);
    };

    /* Pemisah ribuan saja, tanpa prefix & desimal: "100000" -> "100.000" */
    const ribuanSaja = function (nilai) {
        const bersih = String(nilai == null ? '' : nilai).replace(/[^0-9]/g, '');

        if (bersih === '') {
            return '';
        }

        return bersih.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    };

    /* Pasang behavior format pada satu input. */
    const pasang = function (input, tersembunyi) {
        if (!input) {
            return;
        }

        // Input tersembunyi dikenali dari atribut `data-rupiah-tujuan`:
        // nilainya adalah nama field yang harus menerima angkanya.
        //
        // Pencarian memakai `document`, bukan `input.form`: di Kasir field
        // tampilan berada DI LUAR form (formnya hanya membungkus input
        // tersembunyi & tombol simpan), jadi `input.form` bernilai null.
        //
        // Input itu sendiri TIDAK boleh jadi target: kalau iya, setiap
        // ketikan menulis ulang angkanya sendiri dan angka berlipat terus.
        const kandidat = (input.form || document).querySelector(
            '[data-rupiah-tujuan="' + (input.dataset.rupiahAsli || input.name) + '"]'
        );

        const target = kandidat && kandidat !== input ? kandidat : tersembunyi;

        // Angka yang sedang diketik, terpisah dari tampilan.
        let mentah = keAngka(input.value);

        const simpan = function () {
            if (target) {
                target.value = mentah;
            }
        };

        input.addEventListener('focus', function () {
            // Tampilkan angka polos saat mulai diedit.
            input.value = mentah || '';
            input.select();
            simpan();
        });

        input.addEventListener('input', function () {
            // Ambil semua digit dari isi input saat ini, lalu rebuild.
            const semuaDigit = input.value.replace(/\D/g, '');

            mentah = semuaDigit === '' ? 0 : parseInt(semuaDigit, 10);

            input.value = ribuanSaja(mentah);
            simpan();
        });

        input.addEventListener('blur', function () {
            input.value = mentah > 0 ? format(mentah) : '';
            simpan();
        });

        if (target) {
            // Mode 1: nilai dikirim lewat input tersembunyi, jadi field
            // tampilan boleh tetap berisi "Rp 100.000,00".
            return;
        }

        // Mode 2: field ini sendiri yang jadi nama form. Kirim angka polos:
        // server memvalidasi `integer`, jadi "Rp 10.000,00" akan ditolak.
        const form = input.form;

        if (form) {
            form.addEventListener('submit', function () {
                input.value = mentah;
            });
        }
    };

    /* Pasang ke semua input yang ditandai. */
    const pasangSemua = function (akar) {
        (akar || document).querySelectorAll('[data-rupiah]').forEach(function (input) {
            pasang(input);
        });
    };

    /* Isi nilai input rupiah dari kode (bukan dari ketikan pengguna).
     *
     * Dipakai saat JavaScript mengisi field secara programatis — misalnya
     * harga beli otomatis dari produk yang dipilih. State internal input
     * harus ikut diperbarui, kalau tidak ia jadi basi dan submit
     * berikutnya mengirim angka lama.
     */
    const setNilai = function (input, angka) {
        if (!input) {
            return;
        }

        const bersih = String(angka == null ? '' : angka).replace(/[^0-9]/g, '');
        const nilai = bersih === '' ? 0 : parseInt(bersih, 10);

        // Simpan state internal lewat event: helper membaca `input.value`.
        input.value = ribuanSaja(nilai);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    };

    global.Rupiah = {
        format: format,
        keAngka: keAngka,
        ribuanSaja: ribuanSaja,
        pasang: pasang,
        pasangSemua: pasangSemua,
        setNilai: setNilai,
    };

    // Otomatis dipasang begitu DOM siap.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            pasangSemua();
        });
    } else {
        pasangSemua();
    }
})(window);
