@extends('layouts.app')

@section('judul', 'Detail Produk')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>{{ $produk->nama_produk }}</h2>
            <p>Kode Barang {{ $produk->barcode }} · {{ $produk->nama_kategori }}</p>
        </div>
        <div class="flex">
            <a href="{{ route('stok.index') }}" class="tombol abu">Kembali</a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('stok.edit', $produk) }}" class="tombol">Ubah Produk</a>
            @endif
        </div>
    </div>

    {{-- Halaman ini read-only: hanya menampilkan data dan riwayat. Semua
         perubahan stok dilakukan lewat menu lain (Pembelian untuk barang
         datang, atau penyesuaian dari admin), supaya tidak ada dua jalan
         untuk mengubah stok yang bisa saling bertentangan. --}}
    <div class="baris-statistik">
        <div class="statistik {{ $produk->stok <= 0 ? 'merah' : ($produk->status_stok === 'menipis' ? 'kuning' : 'hijau') }}">
            <div class="label">Stok Saat Ini</div>
            <div class="nilai">{{ $produk->stok }} <span class="satuan">{{ $produk->satuan }}</span></div>
        </div>
        <div class="statistik">
            <div class="label">Harga Beli</div>
            <div class="nilai">{{ uang($produk->harga_beli) }}</div>
        </div>
        <div class="statistik">
            <div class="label">Harga Jual</div>
            <div class="nilai">{{ uang($produk->harga_jual) }}</div>
        </div>
        <div class="statistik hijau">
            <div class="label">Margin per Unit</div>
            <div class="nilai">{{ uang($produk->margin) }}</div>
        </div>
    </div>

    <div class="baris-statistik">
        <div class="statistik">
            <div class="label">Kategori</div>
            <div class="nilai kecil">{{ $produk->nama_kategori }}</div>
        </div>
        <div class="statistik">
            <div class="label">Supplier</div>
            <div class="nilai kecil">{{ $produk->nama_supplier }}</div>
        </div>
        <div class="statistik">
            <div class="label">Satuan Kemasan</div>
            <div class="nilai kecil">
                @if ($produk->punya_kemasan)
                    1 {{ $produk->satuan_kemasan }} = {{ $produk->isi_kemasan }} {{ $produk->satuan }}
                @else
                    —
                @endif
            </div>
        </div>
        <div class="statistik">
            <div class="label">Batas Stok Menipis</div>
            <div class="nilai">{{ $produk->stok_minimal }} <span class="satuan">{{ $produk->satuan }}</span></div>
        </div>
    </div>

    <div class="kartu">
        <h3>Riwayat Stok</h3>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Jenis</th>
                        <th class="tengah">Jumlah</th>
                        <th class="tengah">Stok Sesudah</th>
                        <th>Keterangan</th>
                        <th>Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mutasis as $mutasi)
                        <tr>
                            <td>{{ $mutasi->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @php
                                    $warnaJenis = [
                                        'masuk' => 'hijau',
                                        'keluar' => 'merah',
                                        'penyesuaian' => 'kuning',
                                    ][$mutasi->jenis] ?? 'abu';
                                @endphp
                                <span class="lencana {{ $warnaJenis }}">{{ $mutasi->label }}</span>
                            </td>
                            <td class="tengah">
                                <strong>
                                    {{ $mutasi->jenis === 'keluar' ? '−' : '+' }}{{ $mutasi->jumlah }}
                                </strong>
                            </td>
                            <td class="tengah">{{ $mutasi->stok_sesudah }}</td>
                            <td class="kecil">{{ $mutasi->keterangan ?? '—' }}</td>
                            <td class="kecil">{{ $mutasi->user->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="kosong">Belum ada mutasi stok tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginasi">
            <span>Total {{ $mutasis->total() }} mutasi</span>
            <div class="tautan">{{ $mutasis->links('pagination::warungku') }}</div>
        </div>
    </div>
@endsection
