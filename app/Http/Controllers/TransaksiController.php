<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Models\Transaksi;
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
                ->get(['id', 'barcode', 'nama_produk', 'harga_jual', 'stok']),
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
        ]);

        $items = json_decode($request->input('keranjang'), true);

        if (! is_array($items)) {
            return $this->gagal($request, 'Data keranjang tidak valid.');
        }

        try {
            $transaksi = $service->simpanPenjualan(
                $items,
                (int) $request->input('bayar'),
                $request->user()
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

    private function gagal(Request $request, string $pesan)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $pesan], 422);
        }

        return back()->withInput()->with('error', $pesan);
    }
}
