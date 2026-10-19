{{-- Partial form produk. Dipakai oleh stok/create dan stok/edit. --}}
<form action="{{ $produk->exists ? route('stok.update', $produk) : route('stok.store') }}" method="POST">
    @csrf
    @if ($produk->exists)
        @method('PUT')
    @endif

    <div class="baris-form">
        <div class="bidang">
            <label for="barcode">Barcode</label>
            <input type="text" id="barcode" name="barcode" required
                   value="{{ old('barcode', $produk->barcode) }}" placeholder="scan atau ketik kode">
            <div class="bantuan">Harus unik — dipakai saat memindai di kasir.</div>
        </div>

        <div class="bidang">
            <label for="nama_produk">Nama Produk</label>
            <input type="text" id="nama_produk" name="nama_produk" required
                   value="{{ old('nama_produk', $produk->nama_produk) }}">
        </div>
    </div>

    <div class="baris-form">
        <div class="bidang">
            <label for="kategori">Kategori</label>
            <input type="text" id="kategori" name="kategori" list="daftar-kategori" required
                   value="{{ old('kategori', $produk->kategori) }}"
                   placeholder="ketik atau pilih kategori">
            <datalist id="daftar-kategori">
                <option value="Makanan">
                <option value="Minuman">
                <option value="Snack">
                <option value="Sembako">
                <option value="Kebutuhan Rumah">
                <option value="Lainnya">
            </datalist>
        </div>

        <div class="bidang">
            <label for="satuan">Satuan</label>
            <select id="satuan" name="satuan" required>
                @foreach ($daftarSatuan as $s)
                    <option value="{{ $s }}" @selected(old('satuan', $produk->satuan) === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <div class="bantuan">Satuan dasar — stok selalu disimpan dalam satuan ini.</div>
        </div>
    </div>

    <div class="baris-form">
        <div class="bidang">
            <label for="satuan_kemasan">Satuan Kemasan</label>
            <input type="text" id="satuan_kemasan" name="satuan_kemasan" list="daftar-kemasan"
                   value="{{ old('satuan_kemasan', $produk->satuan_kemasan) }}"
                   placeholder="mis. karung, dus, renceng">
            <datalist id="daftar-kemasan">
                <option value="karung">
                <option value="dus">
                <option value="renceng">
                <option value="paket">
                <option value="sak">
            </datalist>
            <div class="bantuan">Kosongkan kalau produk dijual per satuan dasar.</div>
        </div>

        <div class="bidang">
            <label for="isi_kemasan">Isi per Kemasan</label>
            <input type="number" id="isi_kemasan" name="isi_kemasan" min="1"
                   value="{{ old('isi_kemasan', $produk->isi_kemasan) }}"
                   placeholder="mis. 25 untuk karung isi 25 kg">
            <div class="bantuan">1 karung = berapa satuan dasar. Kosongkan kalau tanpa kemasan.</div>
        </div>
    </div>

    <div class="baris-form">
        <div class="bidang">
            <label for="harga_beli">Harga Beli (Rp)</label>
            <input type="number" id="harga_beli" name="harga_beli" min="0" required
                   value="{{ old('harga_beli', $produk->harga_beli) }}">
        </div>

        <div class="bidang">
            <label for="harga_jual">Harga Jual (Rp)</label>
            <input type="number" id="harga_jual" name="harga_jual" min="0" required
                   value="{{ old('harga_jual', $produk->harga_jual) }}">
            <div class="bantuan">Tidak boleh lebih rendah dari harga beli.</div>
        </div>
    </div>

    <div class="baris-form">
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
