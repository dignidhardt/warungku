<?php

namespace App\Services;

use App\Models\Pengeluaran;
use App\Models\Transaksi;
use App\Models\TutupBuku;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tutup buku harian (cash opname).
 *
 * Inti fiturnya bukan menyimpan angka, tapi MEMBANDINGKAN dua angka: uang tunai
 * yang seharusnya ada menurut catatan, dengan uang fisik yang dihitung kasir.
 * Selisihnya sengaja disimpan apa adanya. Kalau sistem hanya menyimpan "sudah
 * cocok", selisih kecil yang berulang (tanda uang hilang) tidak akan pernah
 * terlihat.
 */
class TutupBukuService
{
    /**
     * Uang tunai yang seharusnya ada pada satu tanggal.
     *
     * Rumus: penjualan tunai - retur tunai - pengeluaran tunai - prive.
     * Penjualan non-tunai tidak dihitung karena uangnya tidak ada di laci.
     */
    public function kasSeharusnya(Carbon $tanggal): int
    {
        return array_sum($this->rincianKas($tanggal)['total']);
    }

    /**
     * Rincian perhitungan kas, supaya kasir bisa memastikan sendiri angka yang
     * muncul di layar bukan angka misterius.
     *
     * @return array{penjualan:int, retur:int, pengeluaran:int, prive:int, total:array<string,int>}
     */
    public function rincianKas(Carbon $tanggal): array
    {
        $mulai = $tanggal->copy()->startOfDay();
        $selesai = $tanggal->copy()->endOfDay();
        $dari = $mulai->toDateString();
        $sampai = $selesai->toDateString();

        $penjualanTunai = (int) Transaksi::aktif()
            ->tunai()
            ->whereBetween('created_at', [$mulai, $selesai])
            ->sum('grand_total');

        $returTunai = (int) Transaksi::aktif()
            ->tunai()
            ->whereBetween('created_at', [$mulai, $selesai])
            ->sum('dikembalikan');

        $pengeluarans = Pengeluaran::whereBetween('tanggal', [$dari, $sampai])->get();

        $operasional = (int) $pengeluarans->where('jenis', '!=', Pengeluaran::JENIS_PRIVE)->sum('jumlah');
        $prive = (int) $pengeluarans->where('jenis', Pengeluaran::JENIS_PRIVE)->sum('jumlah');

        return [
            'penjualan' => $penjualanTunai,
            'retur' => $returTunai,
            'operasional' => $operasional,
            'prive' => $prive,
            'total' => [
                'Penjualan tunai' => $penjualanTunai,
                'Retur tunai' => -$returTunai,
                'Pengeluaran operasional' => -$operasional,
                'Prive' => -$prive,
            ],
        ];
    }

    public function simpan(Carbon $tanggal, int $kasFisik, ?string $catatan, ?User $oleh = null): TutupBuku
    {
        if ($kasFisik < 0) {
            throw new RuntimeException('Uang fisik tidak boleh negatif.');
        }

        return DB::transaction(function () use ($tanggal, $kasFisik, $catatan, $oleh) {
            $seharusnya = $this->kasSeharusnya($tanggal);

            // unique(tanggal) menolak tutup buku ganda untuk hari yang sama.
            // Tanpa ini, satu hari bisa punya dua catatan dengan selisih
            // berbeda dan tidak ada yang tahu mana yang benar.
            $tutup = TutupBuku::updateOrCreate(
                ['tanggal' => $tanggal->toDateString()],
                [
                    'kas_seharusnya' => $seharusnya,
                    'kas_fisik' => $kasFisik,
                    'selisih' => $kasFisik - $seharusnya,
                    'catatan' => $catatan,
                    'user_id' => $oleh?->id,
                ]
            );

            // Tandai transaksi hari itu sebagai sudah masuk tutup buku, supaya
            // perubahan setelah tutup buku bisa dideteksi.
            Transaksi::whereBetween('created_at', [
                $tanggal->copy()->startOfDay(),
                $tanggal->copy()->endOfDay(),
            ])->update(['tutup_buku_id' => $tutup->id]);

            return $tutup;
        });
    }
}
