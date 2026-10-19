<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Login dengan username ATAU email.
     *
     * Skema punya kolom `username` (NOT NULL, unique) dan UI manajemen
     * karyawan memakai username, jadi login hanya-email akan menutup akses
     * sebagian akun.
     */
    public function login(Request $request)
    {
        $kredensial = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $this->cekBatasPercobaan($request);

        $identifier = trim($kredensial['identifier']);

        // Satu input untuk dua kemungkinan kolom.
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$field => $identifier, 'password' => $kredensial['password']], $request->boolean('remember'))) {
            RateLimiter::hit($this->kunciPercobaan($request));

            // Dikirim dengan key `identifier` agar view bisa menampilkannya;
            // key yang salah membuat pesan error tidak pernah muncul.
            throw ValidationException::withMessages([
                'identifier' => 'Username/email atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($this->kunciPercobaan($request));
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function cekBatasPercobaan(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->kunciPercobaan($request), 5)) {
            return;
        }

        $detik = RateLimiter::availableIn($this->kunciPercobaan($request));

        throw ValidationException::withMessages([
            'identifier' => "Terlalu banyak percobaan login. Coba lagi dalam {$detik} detik.",
        ]);
    }

    private function kunciPercobaan(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('identifier')).'|'.$request->ip());
    }
}
