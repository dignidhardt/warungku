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

            <div class="bidang">
                <label for="cari-produk">Cari nama atau barcode</label>
                <input type="text" id="cari-produk" list="daftar-produk" autocomplete="off"
                       placeholder="Ketik nama produk atau scan barcode...">
                <datalist id="daftar-produk">
                    @foreach ($produk as $p)
                        <option value="{{ $p->nama_produk }} ({{ $p->barcode }})"
                                data-id="{{ $p->id }}"
                                data-nama="{{ $p->nama_produk }}"
                                data-harga="{{ $p->harga_jual }}"
                                data-stok="{{ $p->stok }}"></option>
                    @endforeach
                </datalist>
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
                <label for="input-bayar">Bayar (tunai)</label>
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

    const daftarProduk = Array.from(document.querySelectorAll('#daftar-produk option')).map(function (opt) {
        return {
            id: Number(opt.dataset.id),
            nama: opt.dataset.nama,
            harga: Number(opt.dataset.harga),
            stok: Number(opt.dataset.stok),
            label: opt.value,
        };
    });

    let keranjang = [];

    const elCari = document.getElementById('cari-produk');
    const elQty = document.getElementById('qty-produk');
    const elInfoStok = document.getElementById('info-stok');
    const elBody = document.querySelector('#tabel-keranjang tbody');
    const elTotal = document.getElementById('teks-total');
    const elBayar = document.getElementById('input-bayar');
    const elKembalian = document.getElementById('teks-kembalian');
    const elKeranjang = document.getElementById('input-keranjang');
    const elBayarHidden = document.getElementById('input-bayar-hidden');
    const btnSimpan = document.getElementById('btn-simpan');
    const wadahPesan = document.getElementById('pesan-kasir');

    const rupiah = function (angka) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka || 0);
    };

    const total = function () {
        return keranjang.reduce(function (jumlah, item) {
            return jumlah + item.harga * item.qty;
        }, 0);
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

    function produkTerpilih() {
        const nilai = elCari.value.trim();

        if (!nilai) {
            return null;
        }

        // Cocokkan lewat label datalist, lalu fallback ke barcode/nama persis
        // supaya scanner yang hanya mengirim angka tetap bekerja.
        return daftarProduk.find(function (p) { return p.label === nilai; })
            || daftarProduk.find(function (p) { return p.nama.toLowerCase() === nilai.toLowerCase(); })
            || null;
    }

    function perbaruiInfoStok() {
        const produk = produkTerpilih();

        if (!produk) {
            elInfoStok.textContent = 'Pilih produk untuk melihat sisa stok.';
            return;
        }

        const diKeranjang = keranjang
            .filter(function (item) { return item.id === produk.id; })
            .reduce(function (jumlah, item) { return jumlah + item.qty; }, 0);

        elInfoStok.textContent = 'Sisa stok: ' + produk.stok + ' pcs'
            + (diKeranjang > 0 ? ' · sudah di keranjang: ' + diKeranjang : '');
    }

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
                        tampilkanPesan('Stok "' + item.nama + '" hanya ' + item.stok + ' pcs.');
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

        const nilaiTotal = total();
        elTotal.textContent = rupiah(nilaiTotal);
        elKeranjang.value = JSON.stringify(keranjang.map(function (item) {
            return { id: item.id, qty: item.qty };
        }));

        hitungKembalian();
        btnSimpan.disabled = keranjang.length === 0;
    }

    function hitungKembalian() {
        const nilaiTotal = total();
        const bayar = parseInt(elBayar.value, 10) || 0;
        const selisih = bayar - nilaiTotal;

        elKembalian.textContent = rupiah(selisih > 0 ? selisih : 0);
        elKembalian.style.color = (bayar > 0 && selisih < 0) ? 'var(--merah)' : 'var(--hijau)';
        elBayarHidden.value = bayar;
    }

    function resetForm() {
        keranjang = [];
        elCari.value = '';
        elQty.value = 1;
        elBayar.value = '';
        gambarKeranjang();
        perbaruiInfoStok();
    }

    document.getElementById('btn-tambah').addEventListener('click', function () {
        const produk = produkTerpilih();

        if (!produk) {
            tampilkanPesan('Produk tidak dikenali. Pilih dari daftar yang muncul saat mengetik.');
            elCari.focus();
            return;
        }

        const qty = Math.max(1, parseInt(elQty.value, 10) || 1);
        const sudahAda = keranjang.find(function (item) { return item.id === produk.id; });
        const totalQty = (sudahAda ? sudahAda.qty : 0) + qty;

        if (totalQty > produk.stok) {
            tampilkanPesan('Stok "' + produk.nama + '" tidak cukup. Sisa: ' + produk.stok + ' pcs.');
            return;
        }

        if (sudahAda) {
            sudahAda.qty = totalQty;
        } else {
            keranjang.push({ id: produk.id, nama: produk.nama, harga: produk.harga, qty: qty, stok: produk.stok });
        }

        tampilkanPesan('');
        elCari.value = '';
        elQty.value = 1;
        elCari.focus();
        gambarKeranjang();
        perbaruiInfoStok();
    });

    // Enter di kolom cari = tambah ke keranjang (mempercepat kerja kasir).
    elCari.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            document.getElementById('btn-tambah').click();
        }
    });

    elCari.addEventListener('input', perbaruiInfoStok);
    elBayar.addEventListener('input', hitungKembalian);

    document.querySelectorAll('[data-uang]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
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

        if (bayar < nilaiTotal) {
            event.preventDefault();
            tampilkanPesan('Uang pembayaran kurang ' + rupiah(nilaiTotal - bayar) + '.');
            return;
        }

        btnSimpan.disabled = true;
        btnSimpan.textContent = 'Menyimpan...';
    });

    gambarKeranjang();
    elCari.focus();
})();
</script>
@endpush
