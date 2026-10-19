@extends('layouts.app')

@section('judul', 'Riwayat Transaksi')

@section('konten')
    <div class="judul-halaman">
        <div>
            <h2>Riwayat Transaksi</h2>
            <p>Semua nota yang tercatat, termasuk yang sudah dibatalkan.</p>
        </div>
        <a href="{{ route('transaksi.index') }}" class="tombol hijau">Transaksi Baru</a>
    </div>

    <div class="kartu">
        <form action="{{ route('transaksi.riwayat') }}" method="GET" class="baris-filter">
            <div class="bidang cari">
                <label for="cari">Cari nomor nota</label>
                <input type="text" id="cari" name="cari" value="{{ request('cari') }}" placeholder="TRX-...">
            </div>

            <div class="bidang">
                <label for="dari">Dari tanggal</label>
                <input type="date" id="dari" name="dari" value="{{ $dari->format('Y-m-d') }}">
            </div>

            <div class="bidang">
                <label for="sampai">Sampai tanggal</label>
                <input type="date" id="sampai" name="sampai" value="{{ $sampai->format('Y-m-d') }}">
            </div>

            <button type="submit" class="tombol">Terapkan</button>
            <a href="{{ route('transaksi.riwayat') }}" class="tombol abu">Hari Ini</a>
        </form>
    </div>

    <div class="kartu">
        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Nomor Nota</th>
                        <th>Waktu</th>
                        <th>Kasir</th>
                        <th class="tengah">Item</th>
                        <th class="angka">Total</th>
                        <th class="tengah">Status</th>
                        <th class="tengah">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transaksi as $trx)
                        <tr>
                            <td><strong>{{ $trx->nomor_transaksi }}</strong></td>
                            <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $trx->kasir->name ?? '—' }}</td>
                            <td class="tengah">
                                {{ collect($trx->items)->sum('qty') }} pcs
                            </td>
                            <td class="angka">Rp {{ number_format($trx->grand_total, 0, ',', '.') }}</td>
                            <td class="tengah">
                                @if ($trx->sudah_dibatalkan)
                                    <span class="lencana merah">Batal</span>
                                    <div class="kecil redup" style="margin-top:4px;">
                                        oleh {{ $trx->pembatal->name ?? '—' }}
                                    </div>
                                @else
                                    <span class="lencana hijau">Selesai</span>
                                @endif
                            </td>
                            <td class="tengah">
                                <div class="flex" style="justify-content:center;">
                                    <a href="{{ route('transaksi.struk', $trx) }}" class="tombol kecil abu">Struk</a>

                                    @if (auth()->user()->isAdmin() && ! $trx->sudah_dibatalkan)
                                        <form action="{{ route('transaksi.batal', $trx) }}" method="POST"
                                              onsubmit="return confirm('Batalkan transaksi {{ $trx->nomor_transaksi }}? Stok akan dikembalikan.')">
                                            @csrf
                                            <button type="submit" class="tombol kecil merah">Batal</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="kosong">Tidak ada transaksi pada rentang tanggal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginasi">
            <span>Menampilkan {{ $transaksi->firstItem() ?? 0 }}–{{ $transaksi->lastItem() ?? 0 }} dari {{ $transaksi->total() }} nota</span>
            <div class="tautan">{{ $transaksi->links('pagination::warungku') }}</div>
        </div>
    </div>
@endsection
