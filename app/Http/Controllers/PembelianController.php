<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\Stok;
use App\Services\PembelianService;
use Illuminate\Http\Request;
use RuntimeException;

class PembelianController extends Controller
{
    public function __construct(private readonly PembelianService $service) {}

    public function index(Request $request)
    {
        $cari = $request->input('cari');

        $pembelians = Pembelian::with('user')
            ->when($cari, fn ($q) => $q->where('nomor_pembelian', 'like', "%{$cari}%")
                ->orWhere('supplier', 'like', "%{$cari}%"))
            ->latest('tanggal')
            ->paginate(20)
            ->withQueryString();

        return view('pembelian.index', [
            'pembelians' => $pembelians,
            'cari' => $cari,
        ]);
    }

    public function create()
    {
        return view('pembelian.create', [
            'produks' => Stok::orderBy('nama_produk')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier' => ['nullable', 'string', 'max:120'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:stok,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.harga_beli' => ['required', 'integer', 'min:0'],
            'items.*.satuan' => ['nullable', 'string'],
        ], [
            'items.required' => 'Tambahkan minimal satu barang.',
        ]);

        try {
            $pembelian = $this->service->simpan(
                $data['items'],
                $data['supplier'] ?? null,
                $data['tanggal'],
                $data['keterangan'] ?? null,
                $request->user()
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pembelian.show', $pembelian)
            ->with('success', 'Pembelian '.$pembelian->nomor_pembelian.' tersimpan, stok sudah bertambah.');
    }

    public function show(Pembelian $pembelian)
    {
        $pembelian->load(['items.produk', 'user']);

        return view('pembelian.show', ['pembelian' => $pembelian]);
    }

    public function destroy(Request $request, Pembelian $pembelian)
    {
        try {
            $this->service->batalkan($pembelian, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pembelian.index')
            ->with('success', 'Pembelian dibatalkan, stok ditarik kembali.');
    }
}
