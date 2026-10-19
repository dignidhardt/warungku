@extends('layouts.app')

@section('judul', 'Kasir')

@push('gaya')
<style>
    .kasir-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
        gap: 18px;
        align-items: start;
    }
    @media (max-width: 980px) {
        .kasir-grid { grid-template-columns: 1fr; }
    }
    .papan-total {
        background: #0f172a;
        color: #6ee7b7;
        border-radius: 10px;
        padding: 18px 20px;
        text-align: right;
        margin-bottom: 16px;
    }
    .papan-total .label {
        display: block;
        font-size: 11px;
        letter-spacing: .1em;
        color: #94a3b8;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    .papan-total strong {
        font-family: ui-monospace, "SF Mono", Menlo, monospace;
        font-size: 30px;
    }
    .baris-bayar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 9px 0;
    }
    .baris-bayar input {
        width: 170px;
        height: 38px;
        text-align: right;
        padding: 0 10px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        font-size: 15px;
    }
    .baris-bayar select {
        width: 170px;
        height: 38px;
        padding: 0 8px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        font-size: 14px;
    }
    .kembalian { color: var(--merah); font-weight: 700; font-size: 16px; }
    .tabel-keranjang td, .tabel-keranjang th { padding: 8px 10px; }
    .hapus-item {
        background: none;
        border: 0;
        color: var(--merah);
        cursor: pointer;
        font-size: 16px;
        line-height: 1;
    }
    .petunjuk { font-size: 12px; color: var(--abu); margin-top: 5px; }
    .pesan-kasir { margin-bottom: 14px; }

    /* Saran pencarian produk: replaces datalist, which is awkward on a phone. */
    .saran-produk {
        position: relative;
    }
    .daftar-saran {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 30;
        background: #fff;
        border: 1px solid var(--garis);
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .13);
        max-height: 260px;
        overflow-y: auto;
        display: none;
    }
    .daftar-saran.aktif { display: block; }
    .saran-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        cursor: pointer;
        border-bottom: 1px solid var(--garis);
    }
    .saran-item:last-child { border-bottom: none; }
    .saran-item:hover, .saran-item.dipilih { background: #f1f5f9; }
    .saran-item .saran-nama { font-size: 14px; }
    .saran-item .saran-meta { font-size: 12px; color: var(--abu); }
    .saran-item .saran-harga { font-size: 13px; font-weight: 600; white-space: nowrap; }
</style>
@endpush

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Kasir</h2>
            <p>Pilih produk, masukkan jumlah, lalu simpan transaksi.</p>
        </div>
        <a href="{{ route('transaksi.riwayat') }}" class="tombol abu">Riwayat Transaksi</a>
    </div>

    {{-- Tempat pesan dari JS: satu wadah, bukan alert() yang memblokir. --}}
    <div id="pesan-kasir"></div>

    <div class="kasir-grid">

        {{-- ---------- Kiri: pilih produk ---------- --}}
        <div class="kartu">
            <h3>Pilih Produk</h3>

            <div class="bidang saran-produk">
                <label for="cari-produk">Cari nama atau barcode</label>
                <input type="text" id="cari-produk" autocomplete="off"
                       placeholder="Ketik nama produk atau scan barcode...">
                <div class="daftar-saran" id="daftar-saran"></div>
                <div class="petunjuk">Scanner barcode akan langsung mengisi kolom ini.</div>
            </div>

            <div class="bidang">
                <label for="qty-produk">Jumlah</label>
                <input type="number" id="qty-produk" value="1" min="1" step="1">
                <div class="petunjuk" id="info-stok">Pilih produk untuk melihat sisa stok.</div>
            </div>

            <button type="button" id="btn-tambah" class="tombol blok">+ Tambahkan ke Keranjang</button>

            @if ($produk->isEmpty())
                <div class="pesan galat" style="margin-top:16px; margin-bottom:0;">
                    Belum ada produk yang stoknya tersedia. Tambahkan produk di menu Produk &amp; Stok.
                </div>
            @endif
        </div>

        {{-- ---------- Kanan: keranjang & pembayaran ---------- --}}
        <div class="kartu">
            <h3>Keranjang</h3>

            <div class="tabel-bungkus">
                <table class="tabel tabel-keranjang" id="tabel-keranjang">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="angka">Harga</th>
                            <th class="tengah">Qty</th>
                            <th class="angka">Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="baris-kosong">
                            <td colspan="5" class="kosong">Belum ada produk di keranjang.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="papan-total" style="margin-top:16px;">
                <span class="label">Grand Total</span>
                <strong id="teks-total">Rp 0</strong>
            </div>

            <div class="baris-bayar">
                <label for="input-diskon">Diskon (Rp)</label>
                <input type="number" id="input-diskon" min="0" step="500" value="0" placeholder="0">
            </div>

            <div class="baris-bayar">
                <label for="input-metode">Metode Bayar</label>
                <select id="input-metode">
                    @foreach ($metode as $nilai => $label)
                        <option value="{{ $nilai }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="baris-bayar">
                <label for="input-bayar">Uang Diterima</label>
                <input type="number" id="input-bayar" min="0" step="1000" placeholder="0">
            </div>

            <div class="baris-bayar">
                <span>Kembalian</span>
                <strong class="kembalian" id="teks-kembalian">Rp 0</strong>
            </div>

            <div class="flex" style="margin-bottom:12px;">
                <button type="button" class="tombol kecil abu" data-uang="uang-pas">Uang pas</button>
                <button type="button" class="tombol kecil abu" data-uang="5000">5.000</button>
                <button type="button" class="tombol kecil abu" data-uang="10000">10.000</button>
                <button type="button" class="tombol kecil abu" data-uang="50000">50.000</button>
                <button type="button" class="tombol kecil abu" data-uang="100000">100.000</button>
            </div>

            <form id="form-transaksi" method="POST" action="{{ route('transaksi.store') }}">
                @csrf
                <input type="hidden" name="keranjang" id="input-keranjang">
                <input type="hidden" name="bayar" id="input-bayar-hidden">
                <input type="hidden" name="diskon" id="input-diskon-hidden">
                <input type="hidden" name="metode_bayar" id="input-metode-hidden">
                <button type="submit" class="tombol hijau blok" id="btn-simpan" disabled>
                    Simpan &amp; Cetak Struk
                </button>
            </form>
        </div>
    </div>
@endsection

@push('skrip')
<script>
(function () {
    'use strict';

    // Daftar produk dikirim dari server sebagai JSON, bukan lewat <datalist>.
    // Alasannya: datalist bawaan browser tampil berbeda di tiap perangkat dan
    // di HP sering tidak bisa dipilih dengan rapi.
    const daftarProduk = @json($produk->map(fn ($p) => [
        'id' => $p->id,
        'nama' => $p->nama_produk,
        'barcode' => $p->barcode,
        'harga' => (int) $p->harga_jual,
        'stok' => (int) $p->stok,
        'satuan' => $p->satuan,
    ]));

    let keranjang = [];

    const elCari = document.getElementById('cari-produk');
    const elQty = document.getElementById('qty-produk');
    const elInfoStok = document.getElementById('info-stok');
    const elBody = document.querySelector('#tabel-keranjang tbody');
    const elTotal = document.getElementById('teks-total');
    const elBayar = document.getElementById('input-bayar');
    const elDiskon = document.getElementById('input-diskon');
    const elMetode = document.getElementById('input-metode');
    const elKembalian = document.getElementById('teks-kembalian');
    const elKeranjang = document.getElementById('input-keranjang');
    const elBayarHidden = document.getElementById('input-bayar-hidden');
    const elDiskonHidden = document.getElementById('input-diskon-hidden');
    const elMetodeHidden = document.getElementById('input-metode-hidden');
    const btnSimpan = document.getElementById('btn-simpan');
    const wadahPesan = document.getElementById('pesan-kasir');
    const wadahSaran = document.getElementById('daftar-saran');

    const rupiah = function (angka) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka || 0);
    };

    const subtotal = function () {
        return keranjang.reduce(function (jumlah, item) {
            return jumlah + item.harga * item.qty;
        }, 0);
    };

    // Diskon tidak boleh lebih besar dari nilai belanja; kalau begitu, total
    // akan negatif dan uang kembalian jadi tidak masuk akal.
    const nilaiDiskon = function () {
        const diskon = parseInt(elDiskon.value, 10) || 0;
        return Math.max(0, Math.min(diskon, subtotal()));
    };

    const total = function () {
        return subtotal() - nilaiDiskon();
    };

    const metodeTunai = function () {
        return elMetode.value === 'tunai';
    };

    function tampilkanPesan(teks, jenis) {
        wadahPesan.innerHTML = '';

        if (!teks) {
            return;
        }

        const div = document.createElement('div');
        div.className = 'pesan ' + (jenis || 'galat') + ' pesan-kasir';
        div.textContent = teks;
        wadahPesan.appendChild(div);
    }

    /* ---------- Pencarian dengan saran ---------- */

    let produkTerpilih = null;
    let saranTerlihat = [];
    let indeksSaran = -1;

    function cariSaran(kata) {
        const kunci = kata.trim().toLowerCase();

        if (!kunci) {
            return [];
        }

        return daftarProduk.filter(function (p) {
            return p.nama.toLowerCase().indexOf(kunci) !== -1
                || String(p.barcode).indexOf(kunci) !== -1;
        }).slice(0, 8);
    }

    function gambarSaran(daftar) {
        wadahSaran.innerHTML = '';
        saranTerlihat = daftar;
        indeksSaran = -1;

        if (!daftar.length) {
            wadahSaran.classList.remove('aktif');
            return;
        }

        daftar.forEach(function (p, i) {
            const item = document.createElement('div');
            item.className = 'saran-item';
            item.dataset.index = i;

            const kiri = document.createElement('div');
            const nama = document.createElement('div');
            nama.className = 'saran-nama';
            nama.textContent = p.nama;
            const meta = document.createElement('div');
            meta.className = 'saran-meta';
            meta.textContent = p.stok > 0
                ? 'stok ' + p.stok + ' ' + p.satuan + ' · ' + p.barcode
                : 'stok habis · ' + p.barcode;
            kiri.appendChild(nama);
            kiri.appendChild(meta);

            const harga = document.createElement('div');
            harga.className = 'saran-harga';
            harga.textContent = rupiah(p.harga);

            item.appendChild(kiri);
            item.appendChild(harga);
            item.addEventListener('mousedown', function (event) {
                // mousedown, bukan click: klik berikutnya Hilangkan fokus input
                // dan menutup daftar sebelum event click sempat terjadi.
                event.preventDefault();
            });
            item.addEventListener('click', function () {
                pilihProduk(p);
            });

            wadahSaran.appendChild(item);
        });

        wadahSaran.classList.add('aktif');
    }

    function sembunyikanSaran() {
        wadahSaran.classList.remove('aktif');
        wadahSaran.innerHTML = '';
        saranTerlihat = [];
        indeksSaran = -1;
    }

    function tandaiSaranTerpilih() {
        wadahSaran.querySelectorAll('.saran-item').forEach(function (item, i) {
            item.classList.toggle('dipilih', i === indeksSaran);
        });
    }

    function pilihProduk(p) {
        produkTerpilih = p;
        elCari.value = p.nama;
        sembunyikanSaran();
        perbaruiInfoStok();
        elQty.focus();
        elQty.select();
    }

    function perbaruiInfoStok() {
        if (!produkTerpilih) {
            elInfoStok.textContent = 'Pilih produk untuk melihat sisa stok.';
            return;
        }

        const diKeranjang = keranjang
            .filter(function (item) { return item.id === produkTerpilih.id; })
            .reduce(function (jumlah, item) { return jumlah + item.qty; }, 0);

        elInfoStok.textContent = 'Sisa stok: ' + produkTerpilih.stok + ' ' + produkTerpilih.satuan
            + (diKeranjang > 0 ? ' · sudah di keranjang: ' + diKeranjang : '');
    }

    /* ---------- Keranjang ---------- */

    function gambarKeranjang() {
        elBody.innerHTML = '';

        if (keranjang.length === 0) {
            const baris = document.createElement('tr');
            baris.innerHTML = '<td colspan="5" class="kosong">Belum ada produk di keranjang.</td>';
            elBody.appendChild(baris);
        } else {
            keranjang.forEach(function (item, indeks) {
                const baris = document.createElement('tr');

                const selProduk = document.createElement('td');
                selProduk.textContent = item.nama;

                const selHarga = document.createElement('td');
                selHarga.className = 'angka';
                selHarga.textContent = rupiah(item.harga);

                const selQty = document.createElement('td');
                selQty.className = 'tengah';
                const inputQty = document.createElement('input');
                inputQty.type = 'number';
                inputQty.min = '1';
                inputQty.value = item.qty;
                inputQty.style.width = '62px';
                inputQty.style.height = '30px';
                inputQty.style.textAlign = 'center';
                inputQty.addEventListener('change', function () {
                    const baru = Math.max(1, parseInt(inputQty.value, 10) || 1);

                    if (baru > item.stok) {
                        tampilkanPesan('Stok "' + item.nama + '" hanya ' + item.stok + ' ' + item.satuan + '.');
                        inputQty.value = item.qty;
                        return;
                    }

                    keranjang[indeks].qty = baru;
                    gambarKeranjang();
                });
                selQty.appendChild(inputQty);

                const selSubtotal = document.createElement('td');
                selSubtotal.className = 'angka';
                selSubtotal.textContent = rupiah(item.harga * item.qty);

                const selAksi = document.createElement('td');
                selAksi.className = 'tengah';
                const btnHapus = document.createElement('button');
                btnHapus.type = 'button';
                btnHapus.className = 'hapus-item';
                btnHapus.innerHTML = '&times;';
                btnHapus.title = 'Hapus item';
                btnHapus.addEventListener('click', function () {
                    keranjang.splice(indeks, 1);
                    gambarKeranjang();
                });
                selAksi.appendChild(btnHapus);

                baris.append(selProduk, selHarga, selQty, selSubtotal, selAksi);
                elBody.appendChild(baris);
            });
        }

        elTotal.textContent = rupiah(total());
        elKeranjang.value = JSON.stringify(keranjang.map(function (item) {
            return { id: item.id, qty: item.qty };
        }));

        hitungKembalian();
        btnSimpan.disabled = keranjang.length === 0;
    }

    function hitungKembalian() {
        const nilaiTotal = total();
        elDiskonHidden.value = nilaiDiskon();
        elMetodeHidden.value = elMetode.value;

        // Untuk QRIS/transfer/debit tidak ada kembalian: uang yang diterima
        // dianggap pas, jadi kolom uang diterima dinonaktifkan.
        if (!metodeTunai()) {
            elBayar.value = nilaiTotal;
            elBayar.disabled = true;
            elKembalian.textContent = rupiah(0);
            elKembalian.style.color = 'var(--hijau)';
            elBayarHidden.value = nilaiTotal;
            return;
        }

        elBayar.disabled = false;
        const bayar = parseInt(elBayar.value, 10) || 0;
        const selisih = bayar - nilaiTotal;

        elKembalian.textContent = rupiah(selisih > 0 ? selisih : 0);
        elKembalian.style.color = (bayar > 0 && selisih < 0) ? 'var(--merah)' : 'var(--hijau)';
        elBayarHidden.value = bayar;
    }

    function resetForm() {
        keranjang = [];
        produkTerpilih = null;
        elCari.value = '';
        elQty.value = 1;
        elBayar.value = '';
        elDiskon.value = '0';
        elMetode.value = 'tunai';
        gambarKeranjang();
        perbaruiInfoStok();
    }

    /* ---------- Kejadian ---------- */

    elCari.addEventListener('input', function () {
        const daftar = cariSaran(elCari.value);

        // Teks yang persis sama dengan nama produk (dipakai scanner barcode
        // yang mengirim label lengkap) langsung dipilih, tanpa perlu klik.
        const persis = daftar.find(function (p) { return p.nama === elCari.value.trim(); });

        if (persis && daftar.length === 1) {
            pilihProduk(persis);
            return;
        }

        produkTerpilih = null;
        perbaruiInfoStok();
        gambarSaran(daftar);
    });

    elCari.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            if (!saranTerlihat.length) {
                return;
            }
            event.preventDefault();
            indeksSaran += (event.key === 'ArrowDown' ? 1 : -1);
            if (indeksSaran < 0) indeksSaran = saranTerlihat.length - 1;
            if (indeksSaran >= saranTerlihat.length) indeksSaran = 0;
            tandaiSaranTerpilih();
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();

            if (indeksSaran >= 0 && saranTerlihat[indeksSaran]) {
                pilihProduk(saranTerlihat[indeksSaran]);
                return;
            }

            document.getElementById('btn-tambah').click();
            return;
        }

        if (event.key === 'Escape') {
            sembunyikanSaran();
        }
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.saran-produk')) {
            sembunyikanSaran();
        }
    });

    document.getElementById('btn-tambah').addEventListener('click', function () {
        if (!produkTerpilih) {
            // Belum pilih dari saran: cari persis berdasarkan teks yang diketik.
            const teks = elCari.value.trim();
            const cocok = daftarProduk.find(function (p) {
                return p.nama.toLowerCase() === teks.toLowerCase() || String(p.barcode) === teks;
            });

            if (!cocok) {
                tampilkanPesan('Produk tidak dikenali. Pilih dari daftar yang muncul saat mengetik.');
                elCari.focus();
                return;
            }

            produkTerpilih = cocok;
        }

        const produk = produkTerpilih;
        const qty = Math.max(1, parseInt(elQty.value, 10) || 1);
        const sudahAda = keranjang.find(function (item) { return item.id === produk.id; });
        const totalQty = (sudahAda ? sudahAda.qty : 0) + qty;

        if (totalQty > produk.stok) {
            tampilkanPesan('Stok "' + produk.nama + '" tidak cukup. Sisa: ' + produk.stok + ' ' + produk.satuan + '.');
            return;
        }

        if (sudahAda) {
            sudahAda.qty = totalQty;
        } else {
            keranjang.push({
                id: produk.id, nama: produk.nama, harga: produk.harga,
                qty: qty, stok: produk.stok, satuan: produk.satuan
            });
        }

        tampilkanPesan('');
        produkTerpilih = null;
        elCari.value = '';
        elQty.value = 1;
        elCari.focus();
        gambarKeranjang();
        perbaruiInfoStok();
    });

    elDiskon.addEventListener('input', function () {
        // Diskon lebih besar dari belanja dikoreksi di layar, bukan diam-diam.
        const diskon = parseInt(elDiskon.value, 10) || 0;
        if (diskon > subtotal()) {
            elDiskon.value = subtotal();
        }
        hitungKembalian();
    });

    elMetode.addEventListener('change', hitungKembalian);
    elBayar.addEventListener('input', hitungKembalian);

    document.querySelectorAll('[data-uang]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            if (!metodeTunai()) {
                tampilkanPesan('Tombol nominal hanya untuk pembayaran tunai.');
                return;
            }
            const nilai = tombol.dataset.uang;
            elBayar.value = (nilai === 'uang-pas') ? total() : Number(nilai);
            hitungKembalian();
        });
    });

    document.getElementById('form-transaksi').addEventListener('submit', function (event) {
        const nilaiTotal = total();
        const bayar = parseInt(elBayar.value, 10) || 0;

        if (keranjang.length === 0) {
            event.preventDefault();
            tampilkanPesan('Keranjang masih kosong.');
            return;
        }

        if (metodeTunai() && bayar < nilaiTotal) {
            event.preventDefault();
            tampilkanPesan('Uang pembayaran kurang ' + rupiah(nilaiTotal - bayar) + '.');
            return;
        }

        btnSimpan.disabled = true;
        btnSimpan.textContent = 'Menyimpan...';
    });

    gambarKeranjang();
    hitungKembalian();
    elCari.focus();
})();
</script>
@endpush
