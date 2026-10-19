<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', [
            'users' => User::orderBy('role')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('users.create', ['user' => new User(['role' => User::ROLE_KASIR])]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        $user = User::create($data);

        return redirect()
            ->route('users.index')
            ->with('success', "Akun '{$user->name}' berhasil dibuat.");
    }

    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validasi($request, $user);

        // Password hanya diubah kalau diisi; kalau kosong, jangan sentuh.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Cegah admin menurunkan perannya sendiri lalu terkunci di luar panel
        // manajemen karyawan.
        if ($user->id === $request->user()->id && ($data['role'] ?? null) !== User::ROLE_ADMIN) {
            return back()
                ->withInput()
                ->with('error', 'Anda tidak bisa mengubah peran akun sendiri menjadi kasir.');
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', "Akun '{$user->name}' berhasil diperbarui.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        // Jangan sampai tidak ada admin tersisa sama sekali.
        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu akun admin.');
        }

        $nama = $user->name;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', "Akun '{$nama}' berhasil dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?User $user = null): array
    {
        $sedangEdit = $user !== null;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'max:50', 'alpha_dash',
                // `username` adalah kolom NOT NULL + unique; tanpa aturan ini
                // penyimpanan gagal di level database.
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [
                $sedangEdit ? 'nullable' : 'required',
                'confirmed',
                Password::min(8),
            ],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_KASIR])],
        ], [
            'username.unique' => 'Username ini sudah dipakai.',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, garis bawah, dan tanda hubung.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);
    }
}
