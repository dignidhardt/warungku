<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Models\Transaksi;
use App\Services\ReturService;
use App\Services\TransaksiService;
use Illuminate\Http\Request;
use RuntimeException;

class TransaksiController extends Controller
{
    /**
     * Halaman kasir.
     */
    public function index()
    {
        return view('transaksi.index', [
            'produk' => Stok::where('stok', '>', 0)
                ->orderBy('nama_produk')
                ->get(['id', 'barcode', 'nama_produk', 'satuan', 'harga_jual', 'stok', 'satuan_kemasan', 'isi_kemasan']),
            'metode' => Transaksi::daftarMetode(),
        ]);
    }

    /**
     * Simpan penjualan. Menerima AJAX (JSON) maupun submit form biasa.
     */
    public function store(Request $request, TransaksiService $service)
    {
        $request->validate([
            'keranjang' => ['required', 'string'],
            'bayar' => ['required', 'integer', 'min:0'],
            'diskon' => ['nullable', 'integer', 'min:0'],
            'metode_bayar' => ['nullable', 'in:tunai,qris,transfer,debit'],
        ]);

        $items = json_decode($request->input('keranjang'), true);

        if (! is_array($items)) {
            return $this->gagal($request, 'Data keranjang tidak valid.');
        }

        try {
            $transaksi = $service->simpanPenjualan(
                $items,
                (int) $request->input('bayar'),
                $request->user(),
                (int) $request->input('diskon', 0),
                (string) $request->input('metode_bayar', Transaksi::METODE_TUNAI)
            );
        } catch (RuntimeException $e) {
            return $this->gagal($request, $e->getMessage());
        }

        // AJAX: kembalikan URL struk supaya kasir bisa langsung mencetak.
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan.',
                'nomor_transaksi' => $transaksi->nomor_transaksi,
                'kembali' => $transaksi->kembali,
                'struk_url' => route('transaksi.struk', $transaksi),
            ]);
        }

        return redirect()
            ->route('transaksi.struk', $transaksi)
            ->with('success', 'Transaksi berhasil disimpan.');
    }

    /**
     * Struk untuk dicetak. Halaman terpisah (tanpa sidebar) agar hasil cetak
     * bersih dan bisa diserahkan ke pembeli.
     */
    public function struk(Transaksi $transaksi)
    {
        return view('transaksi.struk', ['transaksi' => $transaksi]);
    }

    /**
     * Riwayat transaksi dengan filter tanggal dan pencarian nomor nota.
     */
    public function riwayat(Request $request)
    {
        $dari = $request->date('dari') ?? today();
        $sampai = $request->date('sampai') ?? today();

        $transaksi = Transaksi::query()
            ->with(['kasir', 'pembatal'])
            ->when($request->filled('cari'), function ($query) use ($request) {
                $cari = trim((string) $request->get('cari'));
                $query->where('nomor_transaksi', 'like', "%{$cari}%");
            })
            ->whereBetween('created_at', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('transaksi.riwayat', [
            'transaksi' => $transaksi,
            'dari' => $dari,
            'sampai' => $sampai,
        ]);
    }

    /**
     * Batalkan transaksi: stok dikembalikan, nota ditandai batal (tidak dihapus).
     */
    public function batal(Request $request, Transaksi $transaksi, TransaksiService $service)
    {
        try {
            $service->batalkan($transaksi, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Transaksi {$transaksi->nomor_transaksi} dibatalkan dan stok dikembalikan.");
    }

    /**
     * Form retur: menampilkan barang dari nota dengan sisa yang bisa diretur.
     */
    public function formRetur(Transaksi $transaksi)
    {
        $transaksi->load(['returs.items', 'kasir']);

        // Hitung sisa per produk: qty dibeli dikurangi yang sudah diretur.
        $sudahRetur = [];
        foreach ($transaksi->returs as $retur) {
            foreach ($retur->items as $item) {
                $sudahRetur[(int) $item->stok_id] = ($sudahRetur[(int) $item->stok_id] ?? 0) + (int) $item->qty;
            }
        }

        $items = [];
        foreach ($transaksi->items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $sisa = (int) ($item['qty'] ?? 0) - ($sudahRetur[$id] ?? 0);

            if ($sisa > 0) {
                $items[] = [
                    'id' => $id,
                    'nama' => $item['nama'] ?? 'Tanpa nama',
                    'harga' => (int) ($item['harga'] ?? 0),
                    'qty' => (int) ($item['qty'] ?? 0),
                    'sisa' => $sisa,
                ];
            }
        }

        return view('transaksi.retur', [
            'transaksi' => $transaksi,
            'items' => $items,
            'returs' => $transaksi->returs,
        ]);
    }

    public function simpanRetur(Request $request, Transaksi $transaksi, ReturService $service)
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'max:200'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:0'],
        ], [
            'alasan.required' => 'Alasan retur wajib diisi.',
            'items.required' => 'Pilih minimal satu barang yang diretur.',
        ]);

        // Buang baris yang qty-nya 0 (kasir biasanya hanya mengisi sebagian).
        $items = array_values(array_filter(
            $data['items'],
            fn ($item) => (int) $item['qty'] > 0
        ));

        if ($items === []) {
            return back()->withInput()->with('error', 'Belum ada jumlah barang yang diisi.');
        }

        try {
            $retur = $service->simpan($transaksi, $items, $data['alasan'], $request->user());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('transaksi.retur', $transaksi)
            ->with('success', 'Retur '.$retur->nomor_retur.' tersimpan. Uang kembali '.uang($retur->total).'.');
    }

    private function gagal(Request $request, string $pesan)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $pesan], 422);
        }

        return back()->withInput()->with('error', $pesan);
    }
}
