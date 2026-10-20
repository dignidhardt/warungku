@extends('layouts.app')

@section('judul', 'Tambah Pembelian')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Tambah Pembelian</h2>
            <p>Stok akan bertambah dan harga beli produk ikut diperbarui.</p>
        </div>
    </div>

    <form action="{{ route('pembelian.store') }}" method="POST">
        @csrf

        <div class="kartu">
            <div class="judul-panel">
                <h3>Barang yang Dibeli</h3>
                <button type="button" class="tombol abu" id="tambah-item">+ Tambah Baris</button>
            </div>

            <p class="kecil">
                Harga beli diisi manual karena bisa berbeda tiap kali kulakan. Nilai ini yang dipakai
                menghitung laba, jadi isi dengan harga yang benar-benar dibayar.
                Supplier diambil otomatis dari produk yang dipilih.
            </p>

            <div id="daftar-item">
                {{-- Header kolom: DI LUAR loop, jadi tidak ikut terulang
                     setiap kali baris baru ditambah. --}}
                <div class="baris-verbal baris-verbal-pertama">
                    <div class="nama-barang">Barang</div>
                    <div class="supplier-barang">Supplier</div>
                    <div>Qty</div>
                    <div>Harga Beli</div>
                    <div>Satuan</div>
                    <div class="angka">Subtotal</div>
                    <div></div>
                </div>

                @foreach (old('items', [['id' => '', 'qty' => 1, 'harga_beli' => 0]]) as $index => $item)
                    <div class="baris-barang item-pembelian">
                        <div class="nama-barang">
                            <select name="items[{{ $index }}][id]" required class="pilih-produk">
                                <option value="">— pilih barang —</option>
                                @foreach ($produks as $produk)
                                    <option value="{{ $produk->id }}"
                                            data-harga="{{ $produk->harga_beli }}"
                                            data-satuan="{{ $produk->satuan }}"
                                            data-kemasan="{{ $produk->satuan_kemasan }}"
                                            data-isi="{{ $produk->isi_kemasan }}"
                                            data-supplier="{{ $produk->nama_supplier }}"
                                        @selected((int) ($item['id'] ?? 0) === $produk->id)>
                                        {{ $produk->nama_produk }} (stok: {{ $produk->stok }} {{ $produk->satuan }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="supplier-barang" data-supplier-display>—</div>

                        <input type="number" name="items[{{ $index }}][qty]" class="jumlah-beli"
                               min="1" value="{{ $item['qty'] ?? 1 }}" required>
                        <input type="number" name="items[{{ $index }}][harga_beli]" class="harga-beli"
                               min="0" step="100" value="{{ $item['harga_beli'] ?? 0 }}" required>
                        <select name="items[{{ $index }}][satuan]" class="satuan-barang">
                            <option value="">satuan dasar</option>
                        </select>
                        <div class="angka subtotal-beli">Rp 0</div>
                        <button type="button" class="tombol merah kecil hapus-item">Hapus</button>
                    </div>
                @endforeach
            </div>

            <div class="baris-data" style="margin-top: 12px; font-size: 18px;">
                <div><strong>Total Pembelian</strong></div>
                <div class="angka" id="total-pembelian">Rp 0</div>
            </div>
        </div>

        <div class="kartu">
            <h3>Tanggal &amp; Keterangan</h3>

            <div class="baris-filter">
                <div class="bidang">
                    <label for="tanggal">Tanggal</label>
                    <input type="date" id="tanggal" name="tanggal"
                           value="{{ old('tanggal', now()->toDateString()) }}" required>
                </div>

                <div class="bidang" style="flex: 1 1 100%;">
                    <label for="keterangan">Keterangan</label>
                    <input type="text" id="keterangan" name="keterangan" value="{{ old('keterangan') }}"
                           placeholder="Opsional">
                </div>
            </div>
        </div>

        <div class="flex">
            <button type="submit" class="tombol">Simpan Pembelian</button>
            <a href="{{ route('pembelian.index') }}" class="tombol abu">Batal</a>
        </div>
    </form>
@endsection

@push('skrip')
<script>
(function () {
    const daftar = document.getElementById('daftar-item');

    // Opsi satuan untuk setiap baris: satuan dasar + satuan kemasan bila ada.
    function isiOpsiSatuan(select, produk) {
        select.innerHTML = '<option value="">satuan dasar</option>';
        if (!produk) return;
        if (produk.kemasan && produk.isi) {
            const opt = document.createElement('option');
            opt.value = produk.kemasan;
            opt.textContent = produk.kemasan;
            select.appendChild(opt);
        }
    }

    function produkDari(select) {
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return null;
        return {
            harga: parseInt(opt.dataset.harga || '0', 10),
            satuan: opt.dataset.satuan || 'pcs',
            kemasan: opt.dataset.kemasan || '',
            isi: parseInt(opt.dataset.isi || '0', 10),
            supplier: opt.dataset.supplier || ''
        };
    }

    /* Supplier diambil otomatis dari produk yang dipilih. */
    function isiSupplier(baris, produk) {
        const kolom = baris.querySelector('[data-supplier-display]');
        if (!kolom) return;
        kolom.textContent = produk && produk.supplier && produk.supplier !== '—'
            ? produk.supplier
            : '—';
    }

    function hitungSubtotal(baris) {
        const qty = parseInt(baris.querySelector('.jumlah-beli').value || '0', 10);
        const harga = parseInt(baris.querySelector('.harga-beli').value || '0', 10);
        return qty * harga;
    }

    function segarkan() {
        let total = 0;
        daftar.querySelectorAll('.item-pembelian').forEach(function (baris) {
            const subtotal = hitungSubtotal(baris);
            total += subtotal;
            baris.querySelector('.subtotal-beli').textContent =
                'Rp ' + subtotal.toLocaleString('id-ID');
        });
        document.getElementById('total-pembelian').textContent =
            'Rp ' + total.toLocaleString('id-ID');
    }

    // Harga beli diisi otomatis dari harga beli produk, tapi tetap bisa diubah.
    daftar.addEventListener('change', function (e) {
        if (e.target.classList.contains('pilih-produk')) {
            const baris = e.target.closest('.item-pembelian');
            const produk = produkDari(e.target);
            if (produk) {
                baris.querySelector('.harga-beli').value = produk.harga;
                isiOpsiSatuan(baris.querySelector('.satuan-barang'), produk);
            }
            isiSupplier(baris, produk);
        }
        segarkan();
    });

    daftar.addEventListener('input', segarkan);

    daftar.addEventListener('click', function (e) {
        if (!e.target.classList.contains('hapus-item')) return;
        const baris = e.target.closest('.item-pembelian');
        if (daftar.querySelectorAll('.item-pembelian').length > 1) {
            baris.remove();
        } else {
            baris.querySelector('.pilih-produk').value = '';
            baris.querySelector('.jumlah-beli').value = 1;
            baris.querySelector('.harga-beli').value = 0;
            isiOpsiSatuan(baris.querySelector('.satuan-barang'), null);
            isiSupplier(baris, null);
        }
        segarkan();
    });

    document.getElementById('tambah-item').addEventListener('click', function () {
        const template = daftar.querySelector('.item-pembelian').cloneNode(true);
        const index = Date.now();
        template.querySelectorAll('[name]').forEach(function (input) {
            input.name = input.name.replace(/items\[\d+\]/, 'items[' + index + ']');
        });
        template.querySelector('.pilih-produk').value = '';
        template.querySelector('.jumlah-beli').value = 1;
        template.querySelector('.harga-beli').value = 0;
        template.querySelector('.satuan-barang').innerHTML = '<option value="">satuan dasar</option>';
        template.querySelector('[data-supplier-display]').textContent = '—';
        daftar.appendChild(template);
        segarkan();
    });

    // Isi satuan, harga, dan supplier untuk baris yang sudah ter-render dari
    // old input.
    daftar.querySelectorAll('.pilih-produk').forEach(function (select) {
        const produk = produkDari(select);
        if (produk) {
            isiOpsiSatuan(select.closest('.item-pembelian').querySelector('.satuan-barang'), produk);
        }
        isiSupplier(select.closest('.item-pembelian'), produk);
    });

    segarkan();
})();
</script>
@endpush
