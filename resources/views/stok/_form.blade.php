{{-- Partial form produk. Dipakai oleh stok/create dan stok/edit. --}}
<form action="{{ $produk->exists ? route('stok.update', $produk) : route('stok.store') }}" method="POST">
    @csrf
    @if ($produk->exists)
        @method('PUT')
    @endif

    <div class="baris-form">
        <div class="bidang">
            <label for="kategori_id">Kategori</label>
            <select id="kategori_id" name="kategori_id" required>
                <option value="">— pilih kategori —</option>
                @foreach ($daftarKategori as $kategori)
                    <option value="{{ $kategori->id }}"
                            data-kode="{{ $kategori->kode_barang }}"
                            @selected((int) old('kategori_id', $produk->kategori_id) === $kategori->id)>
                        {{ $kategori->nama }} ({{ $kategori->kode_barang }})
                    </option>
                @endforeach
            </select>
            <div class="bantuan">
                <a href="{{ route('master.kategori') }}" target="_blank">Kelola daftar kategori</a>
            </div>
            @if ($daftarKategori->isEmpty())
                <div class="pesan galat" style="margin-top:8px; margin-bottom:0; padding:9px 11px; font-size:12.5px;">
                    Belum ada kategori. Buat dulu di halaman Kategori supaya produk bisa disimpan.
                </div>
            @endif
        </div>

        <div class="bidang">
            <label for="barcode">Kode Barang</label>
            <input type="text" id="barcode" name="barcode" required
                   value="{{ old('barcode', $produk->barcode ?? $kodeBarangOtomatis ?? '') }}"
                   placeholder="otomatis dari kategori">
            <div class="bantuan">
                Terisi otomatis dari Kode Barang kategori (mis. AAA-001). Boleh diubah manual.
            </div>
        </div>
    </div>

    <div class="baris-form">
        <div class="bidang">
            <label for="nama_produk">Nama Produk</label>
            <input type="text" id="nama_produk" name="nama_produk" required
                   value="{{ old('nama_produk', $produk->nama_produk) }}">
        </div>

        <div class="bidang">
            <label for="supplier_id">Supplier</label>
            <select id="supplier_id" name="supplier_id" required>
                <option value="">— pilih supplier —</option>
                @foreach ($daftarSupplier as $supplier)
                    <option value="{{ $supplier->id }}"
                            @selected((int) old('supplier_id', $produk->supplier_id) === $supplier->id)>
                        {{ $supplier->nama }} ({{ $supplier->kode }})
                    </option>
                @endforeach
            </select>
            <div class="bantuan">
                <a href="{{ route('master.supplier') }}" target="_blank">Kelola daftar supplier</a>
            </div>
            @if ($daftarSupplier->isEmpty())
                <div class="pesan galat" style="margin-top:8px; margin-bottom:0; padding:9px 11px; font-size:12.5px;">
                    Belum ada supplier. Buat dulu di halaman Supplier supaya produk bisa disimpan.
                </div>
            @endif
        </div>
    </div>

    <div class="baris-form">
        <div class="bidang">
            <label for="satuan">Satuan</label>
            <select id="satuan" name="satuan" required>
                <option value="">— pilih satuan —</option>
                @foreach ($daftarSatuan as $s)
                    <option value="{{ $s }}" @selected(old('satuan', $produk->satuan) === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <div class="bantuan">
                <a href="{{ route('master.satuan') }}" target="_blank">Kelola daftar satuan</a>
            </div>
        </div>

        <div class="bidang">
            <label for="satuan_kemasan">Satuan Kemasan</label>
            <select id="satuan_kemasan" name="satuan_kemasan">
                <option value="">— tanpa kemasan —</option>
                @foreach ($daftarSatuanKemasan as $s)
                    <option value="{{ $s }}" @selected(old('satuan_kemasan', $produk->satuan_kemasan) === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <div class="bantuan">
                <a href="{{ route('master.satuan-kemasan') }}" target="_blank">Kelola daftar satuan kemasan</a>
            </div>
        </div>
    </div>

    <div class="baris-form">
        <div class="bidang">
            <label for="isi_kemasan">Isi per Kemasan</label>
            <input type="number" id="isi_kemasan" name="isi_kemasan" min="1"
                   value="{{ old('isi_kemasan', $produk->isi_kemasan) }}"
                   placeholder="mis. 25 untuk karung isi 25 kg">
            <div class="bantuan">1 karung = berapa satuan dasar. Kosongkan kalau tanpa kemasan.</div>
        </div>

        <div class="bidang">
            <label for="harga_beli">Harga Beli (Rp)</label>
            <input type="number" id="harga_beli" name="harga_beli" min="0" required
                   value="{{ old('harga_beli', $produk->harga_beli) }}">
        </div>
    </div>

    <div class="baris-form">
        <div class="bidang">
            <label for="harga_jual">Harga Jual (Rp)</label>
            <input type="number" id="harga_jual" name="harga_jual" min="0" required
                   value="{{ old('harga_jual', $produk->harga_jual) }}">
            <div class="bantuan">Tidak boleh lebih rendah dari harga beli.</div>
        </div>

        @if ($produk->exists)
            <div class="bidang">
                <label>Stok Saat Ini</label>
                <input type="text" value="{{ $produk->labelStok($produk->stok) }}" disabled>
                <div class="bantuan">
                    <a href="{{ route('stok.show', $produk) }}">Ubah lewat halaman detail produk</a>
                </div>
            </div>
        @else
            <div class="bidang">
                <label for="stok">Stok Awal</label>
                <input type="number" id="stok" name="stok" min="0" value="{{ old('stok', 0) }}">
            </div>
        @endif
    </div>

    <div class="baris-form">
        <div class="bidang">
            <label for="stok_minimal">Batas Stok Menipis</label>
            <input type="number" id="stok_minimal" name="stok_minimal" min="0"
                   value="{{ old('stok_minimal', $produk->stok_minimal ?? 5) }}">
            <div class="bantuan">Peringatan muncul saat stok mencapai angka ini.</div>
        </div>
    </div>

    <div class="flex">
        <button type="submit" class="tombol">{{ $produk->exists ? 'Simpan Perubahan' : 'Simpan Produk' }}</button>
        <a href="{{ route('stok.index') }}" class="tombol abu">Batal</a>
    </div>
</form>

@push('skrip')
<script>
/*
 * Kode Barang otomatis mengikuti Kode Barang kategori.
 *
 * Format: "<Kode Barang kategori>-<urutan 3 digit>", mis. kategori AAA
 * memberi AAA-001, AAA-002, dan seterusnya. Admin tetap boleh mengubah
 * sendiri nilainya: kalau field sudah diisi manual, ini tidak menimpanya.
 *
 * Peta "kategori id -> kode barang berikutnya" dikirim dari server supaya
 * penomoran ikut memperhitungkan produk yang sudah ada.
 */
(function () {
    'use strict';

    const selectKategori = document.getElementById('kategori_id');
    const inputBarcode = document.getElementById('barcode');

    if (!selectKategori || !inputBarcode) {
        return;
    }

    window.petaKodeBarang = @json($petaKodeBarang ?? []);

    // Barcode yang sudah diisi manual (bukan hasil otomatis) tidak boleh
    // ditimpa saat kategori berubah.
    inputBarcode.addEventListener('input', function () {
        inputBarcode.dataset.manual = '1';
    });

    selectKategori.addEventListener('change', function () {
        if (inputBarcode.dataset.manual === '1') {
            return;
        }

        const opsi = selectKategori.options[selectKategori.selectedIndex];
        const kode = opsi ? (opsi.dataset.kode || '') : '';

        inputBarcode.value = kode ? (window.petaKodeBarang[opsi.value] || '') : '';
    });
})();
</script>
@endpush
