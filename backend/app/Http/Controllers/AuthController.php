<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Show the Velzon login form.
     */
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        // Jika request datang via Inertia (X-Inertia header),
        // paksa browser melakukan full page reload ke halaman login, bukan merender di modal
        if ($request->header('X-Inertia')) {
            return Inertia::location(route('login'));
        }

        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        // Periksa status aktif pengguna
        $targetUser = \App\Models\User::where('email', $credentials['email'])->first();
        if ($targetUser && !$targetUser->is_active) {
            return back()->withErrors([
                'email' => 'Akun Anda dinonaktifkan oleh Administrator. Silakan hubungi koordinator panitia.',
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'))->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Saat logout dipanggil dari Inertia (seperti VelzonLayout atau AppLayout),
        // wajib kembalikan Inertia::location agar browser melakukan full page reload ke /login
        // dan TIDAK menampilkan halaman login di dalam modal mengambang.
        if ($request->header('X-Inertia')) {
            return Inertia::location(route('login'));
        }

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }
}
