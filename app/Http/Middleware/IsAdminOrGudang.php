<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Izinkan akses untuk admin dan gudang.
 *
 * Dipakai untuk halaman yang boleh dibuka keduanya: Products & Stok
 * (read/write) dan Pembelian (read/write).
 *
 * Perbedaannya dengan `IsAdmin`: Gudang boleh mengelola stok & pembelian,
 * tapi TIDAK boleh melihat laporan keuangan, retur, master data karyawan,
 * atau mengubah master data (kategori/satuan).
 */
class IsAdminOrGudang
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ($user->isAdmin() || $user->isGudang())) {
            return $next($request);
        }

        return redirect()
            ->route('home')
            ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
    }
}
