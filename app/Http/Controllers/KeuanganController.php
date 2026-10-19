<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\TutupBuku;
use App\Services\LaporanService;
use App\Services\PembelianService;
use App\Services\TransaksiService;
use App\Services\TutupBukuService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use RuntimeException;

class KeuanganController extends Controller
{
    public function __construct(
        private readonly LaporanService $laporan,
        private readonly PembelianService $pembelian,
        private readonly TutupBukuService $tutupBuku,
        private readonly TransaksiService $transaksi,
    ) {}

    /**
     * Beranda keuangan: ringkasan bulan berjalan + akses cepat.
     */
    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $labaRugi = $this->laporan->labaRugi($bulan, $tahun);

        return view('keuangan.index', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'labaRugi' => $labaRugi,
            'daftarTahun' => $this->laporan->daftarTahun(),
            'pengeluaranTerbaru' => Pengeluaran::latest('tanggal')->limit(5)->get(),
            'pembelianTerbaru' => Pembelian::latest('tanggal')->limit(5)->get(),
        ]);
    }

    public function labaRugi(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        return view('keuangan.laba-rugi', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'data' => $this->laporan->labaRugi($bulan, $tahun),
            'daftarTahun' => $this->laporan->daftarTahun(),
        ]);
    }

    public function bukuKas(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $baris = $this->laporan->bukuKas($bulan, $tahun);

        return view('keuangan.buku-kas', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'baris' => $baris,
            'totalMasuk' => (int) $baris->sum('masuk'),
            'totalKeluar' => (int) $baris->sum('keluar'),
            'daftarTahun' => $this->laporan->daftarTahun(),
        ]);
    }

    public function pengeluaran(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        [$mulai, $selesai] = $this->laporan->rentangBulan($bulan, $tahun);

        return view('keuangan.pengeluaran', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'pengeluarans' => Pengeluaran::whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
                ->latest('tanggal')
                ->paginate(25)
                ->withQueryString(),
            'kategori' => Pengeluaran::daftarKategori(),
            'daftarTahun' => $this->laporan->daftarTahun(),
        ]);
    }

    public function simpanPengeluaran(Request $request)
    {
        $data = $request->validate([
            'jenis' => ['required', 'in:operasional,prive,lain'],
            'kategori' => ['required', 'string', 'max:60'],
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'jumlah.min' => 'Jumlah pengeluaran harus lebih dari nol.',
        ]);

        $data['user_id'] = $request->user()->id;

        Pengeluaran::create($data);

        return back()->with('success', 'Pengeluaran dicatat.');
    }

    public function hapusPengeluaran(Pengeluaran $pengeluaran)
    {
        $pengeluaran->delete();

        return back()->with('success', 'Pengeluaran dihapus.');
    }

    public function tutupBuku(Request $request)
    {
        $tanggal = $request->filled('tanggal')
            ? Carbon::parse($request->input('tanggal'))
            : Carbon::today();

        $rincian = $this->tutupBuku->rincianKas($tanggal);

        return view('keuangan.tutup-buku', [
            'tanggal' => $tanggal,
            'kasSeharusnya' => array_sum($rincian['total']),
            'rincian' => $rincian,
            'riwayat' => TutupBuku::latest('tanggal')->limit(14)->get(),
        ]);
    }

    public function simpanTutupBuku(Request $request)
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'kas_fisik' => ['required', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'kas_fisik.min' => 'Uang fisik tidak boleh negatif.',
        ]);

        // Catatan WAJIB kalau uang tidak cocok. Selisih yang tercatat tanpa
        // alasan tidak bisa ditelusuri, dan justru di situlah selisih paling
        // perlu dijelaskan.
        $seharusnya = $this->tutupBuku->kasSeharusnya(Carbon::parse($data['tanggal']));

        if ((int) $data['kas_fisik'] !== $seharusnya && blank($data['catatan'] ?? null)) {
            return back()->withInput()->with(
                'error',
                'Uang fisik tidak sama dengan seharusnya (Rp '.number_format(abs($seharusnya - (int) $data['kas_fisik']), 0, ',', '.').'). '
                .'Tulis alasan selisihnya.'
            );
        }

        try {
            $hasil = $this->tutupBuku->simpan(
                Carbon::parse($data['tanggal']),
                (int) $data['kas_fisik'],
                $data['catatan'] ?? null,
                $request->user()
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $pesan = $hasil->selisih === 0
            ? 'Tutup buku tersimpan. Kas cocok.'
            : 'Tutup buku tersimpan. Selisih '.uang(abs($hasil->selisih)).' ('.($hasil->selisih > 0 ? 'lebih' : 'kurang').').';

        return redirect()->route('keuangan.tutup-buku', ['tanggal' => $data['tanggal']])
            ->with('success', $pesan);
    }
}
