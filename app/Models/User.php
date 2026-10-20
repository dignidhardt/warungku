<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_KASIR = 'kasir';

    /**
     * Hak akses "Gudang": manage stok & pembelian tanpa bisa melihat
     * laporan keuangan, retur, atau master data karyawan.
     *
     * Rinciannya:
     * - Beranda: read, sama seperti kasir.
     * - Produk & Stok: read/write, sama seperti admin.
     * - Pembelian: read/write, sama seperti admin.
     * - Supplier: read/write, sama seperti admin.
     */
    public const ROLE_GUDANG = 'gudang';

    /**
     * `username` dan `role` WAJIB ada di sini: tanpa itu Laravel membuang
     * kedua kolom diam-diam saat User::create(), sehingga user baru tersimpan
     * tanpa username (query gagal) atau selalu berperan kasir.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Hak akses "Gudang"?
     */
    public function isGudang(): bool
    {
        return $this->role === self::ROLE_GUDANG;
    }

    /**
     * Boleh mengubah data stok (tambah produk, ubah produk, hapus produk)?
     *
     * Gudang punya akses ini sama seperti admin: tugasnya menjaga stok.
     * Kasir TIDAK boleh — kasir hanya menjual, tidak mengubah master data.
     */
    public function bolehKelolaStok(): bool
    {
        return $this->isAdmin() || $this->isGudang();
    }

    /**
     * Boleh mencatat pembelian & mengelola supplier?
     *
     * Gudang boleh, sama seperti admin.
     */
    public function bolehKelolaPembelian(): bool
    {
        return $this->isAdmin() || $this->isGudang();
    }

    /**
     * Boleh mengelola master data (kategori, satuan, satuan kemasan)?
     *
     * Hanya admin: master data memengaruhi seluruh aplikasi dan laporan.
     */
    public function bolehKelolaMasterData(): bool
    {
        return $this->isAdmin();
    }
}
