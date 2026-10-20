{{-- Partial form akun. Dipakai oleh users/create dan users/edit. --}}
<form action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" method="POST">
    @csrf
    @if ($user->exists)
        @method('PUT')
    @endif

    <div class="baris-form">
        <div class="bidang">
            <label for="name">Nama Lengkap</label>
            <input type="text" id="name" name="name" required value="{{ old('name', $user->name) }}">
        </div>

        <div class="bidang">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required
                   value="{{ old('username', $user->username) }}" placeholder="contoh: budi.kasir">
            <div class="bantuan">Dipakai untuk login. Huruf, angka, garis bawah, tanda hubung.</div>
        </div>
    </div>

    <div class="baris-form">
        <div class="bidang">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="{{ old('email', $user->email) }}">
        </div>

        <div class="bidang">
            <label for="role">Hak Akses</label>
            <select id="role" name="role" required>
                <option value="kasir" @selected(old('role', $user->role) === 'kasir')>
                    Kasir — jual & riwayat transaksi
                </option>
                <option value="gudang" @selected(old('role', $user->role) === 'gudang')>
                    Gudang — kelola stok, pembelian & supplier
                </option>
                <option value="admin" @selected(old('role', $user->role) === 'admin')>
                    Admin — akses penuh
                </option>
            </select>
            <div class="bantuan">
                <strong>Kasir</strong>: Beranda, Kasir, Riwayat Transaksi, Laporan, Tutup Buku.<br>
                <strong>Gudang</strong>: Beranda, Produk &amp; Stok, Pembelian, Supplier.<br>
                <strong>Admin</strong>: semua halaman.
            </div>
        </div>
    </div>

    <div class="baris-form">
        <div class="bidang">
            <label for="password">
                Kata Sandi
                @if ($user->exists)
                    <span class="redup kecil">(kosongkan jika tidak diubah)</span>
                @endif
            </label>
            <input type="password" id="password" name="password"
                   autocomplete="new-password"
                   {{ $user->exists ? '' : 'required' }}
                   placeholder="minimal 8 karakter">
        </div>

        <div class="bidang">
            <label for="password_confirmation">Ulangi Kata Sandi</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   autocomplete="new-password"
                   {{ $user->exists ? '' : 'required' }}>
        </div>
    </div>

    <div class="flex">
        <button type="submit" class="tombol">{{ $user->exists ? 'Simpan Perubahan' : 'Buat Akun' }}</button>
        <a href="{{ route('users.index') }}" class="tombol abu">Batal</a>
    </div>
</form>
