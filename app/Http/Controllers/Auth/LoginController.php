<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * 1. Menampilkan Halaman Login
     * Menampilkan form bersih tanpa menu app.blade.php
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * 2. Memproses Ambil Data dari Form Login
     * Mengecek username dan password ke tabel users database
     */
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($request->only('email', 'password'))) {
        $request->session()->regenerate();
        return redirect()->intended('/home');
    }

    return back()->withErrors(['email' => 'Email atau Password salah!']);
}

    /**
     * 3. Fungsi Keluar (Logout)
     * Menghapus sesi login dan mengembalikan pengguna ke halaman login
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        // Bersihkan token keamanan session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Lempar kembali ke halaman depan login
        return redirect('/login');
    }
}