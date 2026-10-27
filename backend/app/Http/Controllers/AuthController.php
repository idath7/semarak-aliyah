<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Registrant;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Services\SipresensiService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir login Admin / Panitia.
     */
    public function showLogin(Request $request)
    {
        if (Auth::guard('web')->check() && !Auth::user()->isRegistrant()) {
            if (Auth::user()->must_change_password || empty(Auth::user()->password)) {
                return redirect()->route('admin.password.first');
            }
            return redirect()->route('admin.dashboard');
        }

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('login'));
        }

        return view('auth.login');
    }

    /**
     * 1. Login Default: Username / NIP / Email + Password
     */
    public function login(Request $request)
    {
        $input = trim((string)($request->input('email') ?: $request->input('identifier') ?: ''));
        $password = (string) $request->input('password');
        $remember = $request->boolean('remember');

        if (empty($input)) {
            return back()->withErrors(['identifier' => 'Username, NIP, atau Email wajib diisi.'])->withInput();
        }
        if (empty($password)) {
            return back()->withErrors(['password' => 'Kata sandi wajib diisi.'])->withInput();
        }

        // Verifikasi Google reCAPTCHA jika diaktifkan di pengaturan
        if ($recaptchaError = $this->verifyRecaptcha($request)) {
            return back()->withErrors(['identifier' => $recaptchaError])->withInput();
        }

        // Cek apakah akun pendaftar mencoba login di panel admin
        $isRegistrant = Registrant::where('email', strtolower($input))->orWhere('phone', $input)->exists();
        if ($isRegistrant) {
            return back()->withErrors([
                'identifier' => 'Akun pendaftar tidak diperbolehkan login di halaman Admin Panel. Silakan masuk melalui Portal Pendaftar (Port 8000).',
            ])->withInput();
        }

        // Cari panitia di tabel users berdasarkan email, nip, atau nomor hp
        $user = User::where(function ($q) use ($input) {
            $q->where('email', strtolower($input))
              ->orWhere('nip', $input)
              ->orWhere('phone', $input);
        })->first();

        if (!$user) {
            return back()->withErrors([
                'identifier' => 'Akun panitia dengan Username / NIP / Email tersebut tidak ditemukan.',
            ])->withInput();
        }

        if (!$user->is_active) {
            return back()->withErrors([
                'identifier' => 'Akun Anda dinonaktifkan oleh Administrator.',
            ])->withInput();
        }

        // Jika password di akun masih kosong (hasil sinkron Sipresensi)
        if (empty($user->password)) {
            return back()->withErrors([
                'identifier' => 'Akun Anda belum memiliki kata sandi mandiri. Silakan gunakan tab "SSO Sipresensi" atau "OTP WhatsApp" untuk masuk dan membuat kata sandi baru.',
            ])->withInput();
        }

        if (!Hash::check($password, $user->password)) {
            return back()->withErrors([
                'password' => 'Kata sandi yang Anda masukkan tidak sesuai.',
            ])->withInput();
        }

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        \App\Models\ActivityLog::logLogin($user, 'Password / NIP');

        if ($user->must_change_password) {
            return redirect()->route('admin.password.first');
        }

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Selamat datang kembali, ' . $user->name . '!');
    }

    /**
     * 2. Login menggunakan SSO Sipresensi (NIP / Username + Password Akun Sipresensi)
     */
    public function loginSsoSipresensi(Request $request)
    {
        $validated = $request->validate([
            'sso_nip'      => ['required', 'string'],
            'sso_password' => ['required', 'string'],
        ], [
            'sso_nip.required'      => 'NIP / Username Sipresensi wajib diisi.',
            'sso_password.required' => 'Password akun Sipresensi wajib diisi.',
        ]);

        $nip = trim($validated['sso_nip']);
        $password = $validated['sso_password'];
        $remember = $request->boolean('remember');

        // Verifikasi Google reCAPTCHA jika diaktifkan di pengaturan
        if ($recaptchaError = $this->verifyRecaptcha($request)) {
            return back()->withErrors(['sso_nip' => $recaptchaError])->withInput(['active_tab' => 'sso']);
        }

        try {
            // Verifikasi kredensial melalui layanan Sipresensi (REST API / Isolated Service tanpa direct DB connection)
            $authResult = SipresensiService::verifyCredentials($nip, $password);

            if (empty($authResult['success'])) {
                return back()->withErrors([
                    'sso_password' => $authResult['message'] ?? 'Kredensial akun Sipresensi tidak sesuai. Silakan periksa kembali kata sandi Sipresensi Anda.',
                ])->withInput(['sso_nip' => $nip, 'active_tab' => 'sso']);
            }

            $sipData = $authResult['data'] ?? [];
            $sipNip = trim($sipData['nip'] ?? $sipData['username'] ?? $nip);
            $cleanEmail = strtolower(trim($sipData['email'] ?? ($sipNip ? "{$sipNip}@sipresensi.local" : '')));

            // Cari user di tabel users Semarak berdasarkan NIP atau Email
            $semarakUser = User::where(function ($q) use ($sipNip, $cleanEmail) {
                if ($sipNip) {
                    $q->where('nip', $sipNip);
                }
                if ($cleanEmail) {
                    $q->orWhere('email', $cleanEmail);
                }
            })->first();

            // Jika belum ada di Semarak, buatkan akun panitia secara instan
            if (!$semarakUser) {
                $semarakUser = User::create([
                    'name'                 => $sipData['name'] ?? "Guru ({$sipNip})",
                    'email'                => $cleanEmail ?: "user_{$sipNip}@sipresensi.local",
                    'nip'                  => $sipNip ?: null,
                    'phone'                => $sipData['phone'] ?? null,
                    'gender'               => $sipData['gender'] ?? null,
                    'password'             => null,
                    'must_change_password' => true,
                    'role'                 => 'panitia',
                    'division'             => 'Acara & Lomba',
                    'is_active'            => true,
                ]);
            } else {
                if (empty($semarakUser->nip) && $sipNip) {
                    $semarakUser->nip = $sipNip;
                    $semarakUser->save();
                }
            }

            if (!$semarakUser->is_active) {
                return back()->withErrors([
                    'sso_nip' => 'Akun panitia Anda sedang dinonaktifkan oleh Administrator.',
                ])->withInput(['sso_nip' => $nip, 'active_tab' => 'sso']);
            }

            Auth::guard('web')->login($semarakUser, $remember);
            $request->session()->regenerate();

            \App\Models\ActivityLog::logLogin($semarakUser, 'SSO Sipresensi');

            // Jika akun belum memiliki password Semarak atau flag must_change_password aktif
            if ($semarakUser->must_change_password || empty($semarakUser->password)) {
                return redirect()->route('admin.password.first')->with('info', "Selamat datang, {$semarakUser->name}! Anda berhasil masuk melalui SSO Sipresensi. Silakan buat kata sandi baru untuk akun Anda.");
            }

            return redirect()->intended(route('admin.dashboard'))->with('success', "Selamat datang kembali, {$semarakUser->name}! Berhasil masuk melalui SSO Sipresensi.");
        } catch (\Throwable $e) {
            return back()->withErrors([
                'sso_nip' => 'Layanan SSO Sipresensi sedang mengalami kendala: ' . $e->getMessage(),
            ])->withInput(['sso_nip' => $nip, 'active_tab' => 'sso']);
        }
    }

    /**
     * 3A. Kirim Kode OTP WhatsApp Panitia
     */
    public function sendWaOtp(Request $request)
    {
        $input = trim((string)$request->input('phone_or_nip', ''));
        if (empty($input)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp, NIP, atau Email panitia wajib diisi.',
            ], 422);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $input);

        $user = User::where(function ($q) use ($input, $cleanPhone) {
            $q->where('nip', $input)
              ->orWhere('email', strtolower($input));
            if (!empty($cleanPhone)) {
                $q->orWhere('phone', $input)
                  ->orWhere('phone', $cleanPhone)
                  ->orWhere('phone', '0' . substr($cleanPhone, 2))
                  ->orWhere('phone', '62' . ltrim($cleanPhone, '0'));
            }
        })->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "Akun panitia dengan identitas '{$input}' tidak ditemukan.",
            ], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun panitia Anda sedang dinonaktifkan oleh Administrator.',
            ], 403);
        }

        if (empty($user->phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini belum memiliki nomor WhatsApp terdaftar. Silakan gunakan login SSO Sipresensi atau hubungi Super Admin.',
            ], 422);
        }

        // Generate OTP 6 digit
        $otp = sprintf('%06d', mt_rand(100000, 999999));
        $user->wa_otp_code = $otp;
        $user->wa_otp_expires_at = now()->addMinutes(5);
        $user->save();

        $eventName = Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026');
        $message = "🔐 *KODE OTP MASUK PANITIA {$eventName}*\n\n"
                 . "Halo *{$user->name}*,\n"
                 . "Kode verifikasi OTP login Anda adalah:\n\n"
                 . "👉 *{$otp}*\n\n"
                 . "Kode berlaku selama 5 menit. Jangan berikan kode ini kepada siapapun.";

        try {
            \App\Services\WhatsAppService::sendMessage($user->phone, $message, 'otp', $user->name, true);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim OTP WA panitia: ' . $e->getMessage());
        }

        // Sensor sebagian nomor HP untuk privasi
        $phone = $user->phone;
        $maskedPhone = (strlen($phone) > 6) ? substr($phone, 0, 4) . '****' . substr($phone, -3) : $phone;

        return response()->json([
            'success'      => true,
            'message'      => "Kode OTP telah dikirimkan ke nomor WhatsApp {$maskedPhone}.",
            'phone'        => $user->phone,
            'phone_or_nip' => $input,
        ]);
    }

    /**
     * 3B. Verifikasi Kode OTP WhatsApp Panitia & Login
     */
    public function verifyWaOtp(Request $request)
    {
        $input = trim((string)$request->input('phone_or_nip', ''));
        $otp = trim((string)$request->input('otp_code', ''));

        if (empty($input) || empty($otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Identitas akun dan kode OTP wajib diisi.',
            ], 422);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $input);

        $user = User::where(function ($q) use ($input, $cleanPhone) {
            $q->where('nip', $input)
              ->orWhere('email', strtolower($input));
            if (!empty($cleanPhone)) {
                $q->orWhere('phone', $input)
                  ->orWhere('phone', $cleanPhone)
                  ->orWhere('phone', '0' . substr($cleanPhone, 2))
                  ->orWhere('phone', '62' . ltrim($cleanPhone, '0'));
            }
        })->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun panitia tidak ditemukan.',
            ], 404);
        }

        if (empty($user->wa_otp_code) || $user->wa_otp_code !== $otp) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP yang Anda masukkan salah.',
            ], 422);
        }

        if (!$user->wa_otp_expires_at || $user->wa_otp_expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP sudah kadaluwarsa. Silakan minta kode baru.',
            ], 422);
        }

        // Bersihkan OTP
        $user->wa_otp_code = null;
        $user->wa_otp_expires_at = null;
        $user->save();

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        \App\Models\ActivityLog::logLogin($user, 'OTP WhatsApp');

        $redirectUrl = ($user->must_change_password || empty($user->password))
            ? route('admin.password.first')
            : route('admin.dashboard');

        return response()->json([
            'success'  => true,
            'message'  => "Berhasil masuk sebagai {$user->name}.",
            'redirect' => $redirectUrl,
        ]);
    }

    /**
     * 4A. Inisiasi Login Akun Google Panitia
     */
    public function adminGoogleLogin(Request $request)
    {
        $clientId = Setting::get('google_client_id', env('GOOGLE_CLIENT_ID', ''));
        if (!empty($clientId)) {
            $redirectUri = url('/admin/auth/google/callback');
            $params = http_build_query([
                'client_id'     => $clientId,
                'redirect_uri'  => $redirectUri,
                'response_type' => 'code',
                'scope'         => 'openid profile email',
                'access_type'   => 'online',
                'prompt'        => 'select_account',
            ]);
            return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
        }

        // Mode Local / Offline: Masuk cepat dengan akun panitia/admin yang terdaftar
        if (config('app.env') === 'local') {
            $existingUser = User::where('is_active', true)
                ->where(function ($q) {
                    $q->where('email', 'like', '%@gmail.com')
                      ->orWhere('role', 'super_admin')
                      ->orWhere('role', 'admin');
                })->first() ?: User::where('is_active', true)->first();

            if ($existingUser) {
                Auth::guard('web')->login($existingUser, true);
                $request->session()->regenerate();

                \App\Models\ActivityLog::logLogin($existingUser, 'Akun Google (Cepat)');

                if ($existingUser->must_change_password || empty($existingUser->password)) {
                    return redirect()->route('admin.password.first')->with('info', "Selamat datang, {$existingUser->name}! Anda berhasil masuk cepat akun Google (mode lokal). Silakan buat kata sandi baru.");
                }

                return redirect()->intended(route('admin.dashboard'))
                    ->with('success', "Selamat datang kembali, {$existingUser->name}! Berhasil masuk cepat via Akun Google.");
            }
        }

        return redirect()->route('login')->withErrors([
            'google' => 'Google Client ID belum dikonfigurasi di Pengaturan sistem.',
        ])->withInput(['active_tab' => 'google']);
    }

    /**
     * 4B. Login Direct dengan Email Google Panitia (Fallback Offline / Tanpa OAuth)
     */
    public function adminGoogleDirectLogin(Request $request)
    {
        $email = strtolower(trim((string)$request->input('google_email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['google' => 'Format email Google tidak valid.'])->withInput(['active_tab' => 'google']);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors([
                'google' => "Email Google '{$email}' belum terdaftar sebagai akun panitia SEMARAK.",
            ])->withInput(['active_tab' => 'google']);
        }

        if (!$user->is_active) {
            return back()->withErrors([
                'google' => 'Akun panitia Anda dinonaktifkan oleh Administrator.',
            ])->withInput(['active_tab' => 'google']);
        }

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        \App\Models\ActivityLog::logLogin($user, 'Akun Google (Direct)');

        if ($user->must_change_password || empty($user->password)) {
            return redirect()->route('admin.password.first')->with('info', "Selamat datang, {$user->name}! Silakan buat kata sandi baru untuk akun Anda.");
        }

        return redirect()->intended(route('admin.dashboard'))->with('success', "Selamat datang kembali, {$user->name}!");
    }

    /**
     * 4C. Handle Callback Google OAuth untuk Admin/Panitia
     */
    public function handleAdminGoogleCallback(Request $request)
    {
        $code = $request->query('code');
        $clientId = Setting::get('google_client_id', env('GOOGLE_CLIENT_ID', ''));
        $clientSecret = Setting::get('google_client_secret', env('GOOGLE_CLIENT_SECRET', ''));

        if (empty($code) || empty($clientId) || empty($clientSecret)) {
            return redirect()->route('login')->withErrors([
                'google' => 'Otorisasi Google OAuth gagal atau belum lengkap.',
            ]);
        }

        try {
            $redirectUri = url('/admin/auth/google/callback');
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code'          => $code,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
                'grant_type'    => 'authorization_code',
            ]);

            if (!$response->successful() || empty($response['access_token'])) {
                return redirect()->route('login')->withErrors([
                    'google' => 'Gagal mendapatkan token akses dari Google.',
                ]);
            }

            $userResponse = Http::withToken($response['access_token'])->get('https://www.googleapis.com/oauth2/v3/userinfo');
            if (!$userResponse->successful() || empty($userResponse['email'])) {
                return redirect()->route('login')->withErrors([
                    'google' => 'Gagal mengambil informasi profil Google.',
                ]);
            }

            $googleEmail = strtolower(trim($userResponse['email']));
            $googleId = $userResponse['sub'] ?? null;

            $user = User::where('email', $googleEmail)
                ->orWhere(function ($q) use ($googleId) {
                    if ($googleId) $q->where('google_id', $googleId);
                })->first();

            if (!$user) {
                return redirect()->route('login')->withErrors([
                    'google' => "Email Google '{$googleEmail}' belum terdaftar sebagai akun panitia SEMARAK.",
                ]);
            }

            if (!$user->is_active) {
                return redirect()->route('login')->withErrors([
                    'google' => 'Akun panitia Anda dinonaktifkan oleh Administrator.',
                ]);
            }

            if ($googleId && empty($user->google_id)) {
                $user->google_id = $googleId;
                $user->save();
            }

            Auth::guard('web')->login($user, true);
            $request->session()->regenerate();

            \App\Models\ActivityLog::logLogin($user, 'Akun Google (OAuth)');

            if ($user->must_change_password || empty($user->password)) {
                return redirect()->route('admin.password.first');
            }

            return redirect()->intended(route('admin.dashboard'))->with('success', "Selamat datang kembali, {$user->name}!");
        } catch (\Throwable $e) {
            Log::error('Admin Google OAuth error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'google' => 'Terjadi kesalahan saat memproses login Google: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 5. Halaman Wajib Buat / Ganti Kata Sandi Pertama Kali
     */
    public function showFirstTimePassword(Request $request)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('login');
        }

        $user = Auth::guard('web')->user();

        // Jika sudah memiliki password dan tidak berstatus must_change_password, alihkan ke dashboard
        if (!$user->must_change_password && !empty($user->password)) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.first_time_password', [
            'user' => $user,
        ]);
    }

    /**
     * Simpan Kata Sandi Baru Saat Login Pertama
     */
    public function storeFirstTimePassword(Request $request)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('login');
        }

        $user = Auth::guard('web')->user();

        $validated = $request->validate([
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ], [
            'password.required'  => 'Kata sandi baru wajib diisi.',
            'password.min'       => 'Kata sandi minimal terdiri dari 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->must_change_password = false;
        $user->save();

        \App\Models\ActivityLog::record(
            'password_change',
            'Aktivasi Sandi Baru Panitia',
            "Panitia {$user->name} berhasil membuat dan mengaktivasi kata sandi baru.",
            ['user' => $user, 'highlight' => $user->name, 'activity_type' => 'auth']
        );

        return redirect()->route('admin.dashboard')->with('success', 'Kata sandi baru berhasil disimpan! Selamat bertugas di Semarak Aliyah XIV.');
    }

    /**
     * Log out pengguna panitia.
     */
    public function logout(Request $request)
    {
        $user = Auth::guard('web')->user();
        if ($user) {
            \App\Models\ActivityLog::logLogout($user);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('login'));
        }

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }

    /**
     * Verifikasi Google reCAPTCHA jika diaktifkan di pengaturan sistem.
     */
    protected function verifyRecaptcha(Request $request): ?string
    {
        $enabled = filter_var(Setting::get('admin_login_recaptcha_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
        if (!$enabled) {
            return null;
        }

        $token = $request->input('g-recaptcha-response');
        $secretKey = trim((string)Setting::get('recaptcha_secret_key', env('RECAPTCHA_SECRET_KEY', '')));
        $bypassLocal = filter_var(Setting::get('admin_login_recaptcha_bypass_local', '1'), FILTER_VALIDATE_BOOLEAN);
        $isLocal = in_array(config('app.env'), ['local', 'testing']) || in_array($request->ip(), ['127.0.0.1', '::1']);

        // Jika mode bypass lokal aktif dan secret key belum diset
        if ($bypassLocal && $isLocal && empty($secretKey)) {
            return null;
        }

        if (empty($token)) {
            return 'Verifikasi reCAPTCHA ("Saya bukan robot") wajib dicentang.';
        }

        // Kunci testing resmi Google selalu lolos setelah checkbox dicentang
        if ($secretKey === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe') {
            return null;
        }

        if (!empty($secretKey)) {
            try {
                $response = Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret'   => $secretKey,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

                if (!$response->successful() || empty($response->json('success'))) {
                    return 'Verifikasi reCAPTCHA tidak valid atau kedaluwarsa. Silakan centang ulang.';
                }
            } catch (\Throwable $e) {
                Log::warning('Gagal verifikasi reCAPTCHA: ' . $e->getMessage());
                if (!$bypassLocal) {
                    return 'Layanan verifikasi reCAPTCHA sedang mengalami kendala jaringan.';
                }
            }
        }

        return null;
    }
}
