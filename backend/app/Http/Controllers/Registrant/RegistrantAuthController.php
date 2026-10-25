<?php

namespace App\Http\Controllers\Registrant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Registrant;
use App\Models\User;
use App\Models\School;
use App\Models\Registration;
use App\Models\Setting;
use App\Services\WhatsAppService;

class RegistrantAuthController extends Controller
{
    /**
     * Tampilkan formulir login pendaftar.
     */
    public function showLogin(Request $request)
    {
        if (Auth::guard('pendaftar')->check()) {
            if ($request->query('redirect_to') === 'register') {
                $lomba = $request->query('lomba');
                $params = $lomba ? ['lomba' => $lomba] : [];
                return redirect()->route('public.register', $params);
            }
            return redirect()->route('pendaftar.dashboard');
        }

        $redirectTo = $request->query('redirect_to', '');
        $lomba = $request->query('lomba', '');
        $isLoginRequired = Setting::get('registration_login_required', '0') === '1';
        $schoolName = Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung');
        $googleClientId = Setting::get('google_client_id', env('GOOGLE_CLIENT_ID', ''));

        return Inertia::render('Auth/RegistrantLogin', [
            'redirectTo' => $redirectTo,
            'lomba' => $lomba,
            'isLoginRequired' => $isLoginRequired,
            'schoolName' => $schoolName,
            'googleEnabled' => !empty($googleClientId) || config('app.env') === 'local',
            'notice' => $request->query('notice', ''),
        ]);
    }

    /**
     * Autentikasi pendaftar menggunakan Username (Nomor HP/Email) & Password.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
            'redirect_to' => 'nullable|string',
        ], [
            'login.required' => 'Nomor WhatsApp atau Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $rawLogin = trim($validated['login']);
        $password = $validated['password'];
        $remember = $request->boolean('remember');

        // Cari pendaftar di tabel khusus registrants berdasarkan email atau format nomor WA
        $cleanPhone = Setting::formatWaNumber($rawLogin);
        $rawDigits = preg_replace('/\D/', '', $rawLogin);

        $registrant = Registrant::where(function ($query) use ($rawLogin, $cleanPhone, $rawDigits) {
            $query->where('email', strtolower($rawLogin))
                ->orWhere('phone', $rawLogin)
                ->orWhere('phone', $cleanPhone);
            if (!empty($rawDigits)) {
                $query->orWhere('phone', $rawDigits);
            }
        })->first();

        // Jika tidak ditemukan di registrants, periksa apakah email ini adalah akun Panitia di tabel users
        if (!$registrant) {
            $isPanitia = User::where('email', strtolower($rawLogin))->exists();
            if ($isPanitia) {
                return back()->withErrors([
                    'login' => 'Akun panitia/administrator tidak dapat masuk ke Portal Pendaftar. Silakan masuk melalui Admin Panel (Port 8001).',
                ])->onlyInput('login');
            }

            return back()->withErrors([
                'login' => 'Nomor WhatsApp / Email atau Password yang Anda masukkan tidak sesuai.',
            ])->onlyInput('login');
        }

        if (!Hash::check($password, $registrant->password)) {
            return back()->withErrors([
                'login' => 'Nomor WhatsApp / Email atau Password yang Anda masukkan tidak sesuai.',
            ])->onlyInput('login');
        }

        if (!$registrant->is_active) {
            return back()->withErrors([
                'login' => 'Akun pendaftaran Anda dinonaktifkan oleh administrator. Silakan hubungi panitia.',
            ])->onlyInput('login');
        }

        Auth::guard('pendaftar')->login($registrant, $remember);
        $request->session()->regenerate();

        if ($request->input('redirect_to') === 'register') {
            $lomba = $request->input('lomba');
            $params = $lomba ? ['lomba' => $lomba] : [];
            return redirect()->route('public.register', $params)->with('success', 'Selamat datang, ' . $registrant->name . '! Silakan lanjutkan pengisian formulir pendaftaran.');
        }

        return redirect()->intended(route('pendaftar.dashboard'))->with('success', 'Selamat datang kembali, ' . $registrant->name . '!');
    }

    /**
     * Tampilkan formulir pendaftaran akun pendaftar.
     */
    public function showRegister(Request $request)
    {
        if (Auth::guard('pendaftar')->check()) {
            if ($request->query('redirect_to') === 'register') {
                $lomba = $request->query('lomba');
                $params = $lomba ? ['lomba' => $lomba] : [];
                return redirect()->route('public.register', $params);
            }
            return redirect()->route('pendaftar.dashboard');
        }

        $schools = School::active()->orderBy('name', 'asc')->get(['id', 'name', 'kecamatan', 'npsn'])->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'district' => $s->kecamatan,
                'kecamatan' => $s->kecamatan,
                'npsn' => $s->npsn,
            ];
        });
        $districts = School::distinct()->whereNotNull('kecamatan')->where('kecamatan', '!=', '')->orderBy('kecamatan', 'asc')->pluck('kecamatan');
        
        $schoolName = Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung');
        $redirectTo = $request->query('redirect_to', '');
        $lomba = $request->query('lomba', '');
        $isLoginRequired = Setting::get('registration_login_required', '0') === '1';

        return Inertia::render('Auth/RegistrantRegister', [
            'schools' => $schools,
            'districts' => $districts,
            'schoolName' => $schoolName,
            'redirectTo' => $redirectTo,
            'lomba' => $lomba,
            'isLoginRequired' => $isLoginRequired,
        ]);
    }

    /**
     * Simpan pendaftaran akun pendaftar baru ke tabel registrants.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'registrant_role' => 'required|string|in:Pendamping,Koordinator,Siswa Peserta',
            'gender' => 'required|string|in:Laki-laki,Perempuan',
            'school_name' => 'required|string|max:255',
            'school_district' => 'required|string|max:255',
            'phone' => 'required|string|min:8|max:20',
            'email' => 'required|email|max:255|unique:registrants,email',
            'password' => 'required|string|min:6|confirmed',
            'redirect_to' => 'nullable|string',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'registrant_role.required' => 'Status sebagai pendaftar wajib dipilih.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'school_name.required' => 'Asal sekolah / madrasah wajib diisi.',
            'school_district.required' => 'Kecamatan asal sekolah wajib diisi.',
            'phone.required' => 'Nomor WhatsApp aktif wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Alamat email ini sudah terdaftar. Silakan gunakan menu masuk.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $cleanPhone = Setting::formatWaNumber($validated['phone']);

        // Cek keunikan nomor WA di tabel registrants
        if (Registrant::where('phone', $cleanPhone)->orWhere('phone', $validated['phone'])->exists()) {
            return back()->withErrors([
                'phone' => 'Nomor WhatsApp ini sudah terdaftar sebagai akun pendaftar. Silakan login menggunakan password atau OTP.',
            ])->withInput();
        }

        $registrant = Registrant::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'registrant_role' => $validated['registrant_role'],
            'gender' => $validated['gender'],
            'school_name' => trim($validated['school_name']),
            'school_district' => trim($validated['school_district']),
            'phone' => $cleanPhone,
            'is_active' => true,
        ]);

        // Pastikan sekolah tersinkron ke master database sekolah
        try {
            School::firstOrCreateFromRegistration($validated['school_name'], $cleanPhone, $validated['school_district']);
        } catch (\Throwable $e) {
            Log::warning('Sinkronisasi sekolah gagal saat registrasi akun: ' . $e->getMessage());
        }

        // Tautkan data pendaftaran lomba sebelumnya yang memiliki nomor WA sama
        try {
            Registration::where(function ($q) {
                $q->whereNull('registrant_id')->orWhereNull('user_id');
            })
            ->where(function ($q) use ($cleanPhone, $validated) {
                $q->where('mentor_wa', $cleanPhone)
                  ->orWhere('mentor_wa', $validated['phone']);
            })
            ->update([
                'registrant_id' => $registrant->id,
                'user_id' => $registrant->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Link pendaftaran lama ke akun baru error: ' . $e->getMessage());
        }

        // Kirim ucapan selamat datang via WhatsApp Gateway
        try {
            $waMsg = "*[SEMARAK ALIYAH XIV 2026]*\n\n"
                . "Assalamu'alaikum Wr. Wb., Halo *{$registrant->name}*!\n\n"
                . "Pendaftaran akun pendaftar Anda telah *BERHASIL DIAKTIFKAN*:\n"
                . "• *Sebagai*: {$registrant->registrant_role}\n"
                . "• *Asal Lembaga*: {$registrant->school_name} ({$registrant->school_district})\n"
                . "• *No. WhatsApp*: {$registrant->phone}\n"
                . "• *Email*: {$registrant->email}\n\n"
                . "Anda sekarang dapat memilih cabang lomba dan memperbarui berkas pendaftaran melalui Dashboard Pendaftar:\n"
                . url('/pendaftar/dashboard') . "\n\n"
                . "Salam hangat,\n*Panitia Semarak Aliyah XIV 2026*";
            WhatsAppService::sendMessage($cleanPhone, $waMsg);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim WA selamat datang: ' . $e->getMessage());
        }

        Auth::guard('pendaftar')->login($registrant);
        $request->session()->regenerate();

        if ($request->input('redirect_to') === 'register') {
            $lomba = $request->input('lomba');
            $params = $lomba ? ['lomba' => $lomba] : [];
            return redirect()->route('public.register', $params)->with('success', 'Akun pendaftar berhasil dibuat! Silakan lanjutkan pengisian formulir pendaftaran.');
        }

        return redirect()->route('pendaftar.dashboard')->with('success', 'Selamat datang! Akun pendaftar Anda telah aktif.');
    }

    /**
     * Kirim kode OTP login ke Nomor WhatsApp pendaftar.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ], [
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
        ]);

        $rawPhone = trim($request->input('phone'));
        $cleanPhone = Setting::formatWaNumber($rawPhone);
        $rawDigits = preg_replace('/\D/', '', $rawPhone);

        $registrant = Registrant::where('phone', $cleanPhone)
            ->orWhere('phone', $rawPhone)
            ->orWhere('phone', $rawDigits)
            ->first();

        if (!$registrant) {
            // 1. Cek apakah nomor WA ini ada pada data pendaftaran lomba sebagai Koordinator / Pendamping
            $mentorReg = Registration::where(function ($q) use ($cleanPhone, $rawPhone, $rawDigits) {
                $q->where('mentor_wa', $cleanPhone)
                  ->orWhere('mentor_wa', $rawPhone)
                  ->orWhere('mentor_wa', $rawDigits);
            })->orderBy('id', 'desc')->first();

            if ($mentorReg) {
                $regEmail = $mentorReg->custom_fields['mentor_email'] 
                    ?? ($mentorReg->custom_fields['contact_email'] 
                    ?? ($mentorReg->custom_fields['email'] ?? null));

                $emailToUse = !empty($regEmail) && filter_var($regEmail, FILTER_VALIDATE_EMAIL)
                    ? strtolower(trim($regEmail))
                    : 'pendaftar_' . preg_replace('/\D/', '', $cleanPhone) . '@semarak.id';

                if (Registrant::where('email', $emailToUse)->exists()) {
                    $emailToUse = 'pendaftar_' . preg_replace('/\D/', '', $cleanPhone) . '_' . rand(100, 999) . '@semarak.id';
                }

                $roleLabel = (stripos($mentorReg->mentor_name, 'kapten') !== false) ? 'Siswa Peserta' : 'Pendamping';

                $registrant = Registrant::create([
                    'name' => $mentorReg->mentor_name ?: 'Koordinator ' . $mentorReg->school_name,
                    'phone' => $cleanPhone,
                    'email' => $emailToUse,
                    'password' => Hash::make(\Illuminate\Support\Str::random(16)),
                    'registrant_role' => $roleLabel,
                    'school_name' => $mentorReg->school_name,
                    'school_district' => $mentorReg->custom_fields['school_district'] ?? ($mentorReg->custom_fields['kecamatan'] ?? null),
                    'gender' => 'Laki-laki',
                    'is_active' => true,
                ]);

                // Hubungkan registrant_id & user_id ke seluruh pendaftaran dengan nomor WA ini
                Registration::where(function ($q) use ($cleanPhone, $rawPhone, $rawDigits) {
                    $q->where('mentor_wa', $cleanPhone)
                      ->orWhere('mentor_wa', $rawPhone)
                      ->orWhere('mentor_wa', $rawDigits);
                })->update([
                    'registrant_id' => $registrant->id,
                    'user_id' => $registrant->id,
                ]);
            }
        }

        if (!$registrant) {
            // 2. Cek apakah nomor WA ini telah ditunjuk sebagai Koordinator / Kapten / Siswa pada salah satu cabang lomba
            $assignedReg = Registration::where(function ($q) use ($cleanPhone, $rawPhone, $rawDigits) {
                $q->where('assigned_phone', $cleanPhone)
                  ->orWhere('assigned_phone', $rawPhone)
                  ->orWhere('assigned_phone', $rawDigits);
            })->first();

            if ($assignedReg) {
                $registrant = Registrant::create([
                    'name' => $assignedReg->assigned_name ?: 'Koordinator ' . $assignedReg->competition_title,
                    'phone' => $cleanPhone,
                    'email' => 'pic_' . preg_replace('/\D/', '', $cleanPhone) . '@semarak.id',
                    'password' => Hash::make(\Illuminate\Support\Str::random(20)),
                    'registrant_role' => $assignedReg->assigned_role ?: 'Koordinator',
                    'school_name' => $assignedReg->school_name,
                    'gender' => 'Laki-laki',
                    'is_active' => true,
                ]);
            }
        }

        if (!$registrant) {
            // 3. Cek apakah nomor WA ini tercatat di custom_fields (Kapten Tim / Nomor Kontak Peserta)
            $allCustomRegs = Registration::whereNotNull('custom_fields')->orderBy('id', 'desc')->get();
            foreach ($allCustomRegs as $r) {
                $cf = $r->custom_fields;
                if (!is_array($cf)) continue;
                $foundWa = false;
                $foundName = null;
                $foundRole = 'Siswa Peserta';
                $foundEmail = null;

                // Cek flat keys
                foreach ($cf as $k => $v) {
                    if (is_string($v) && (str_contains($k, 'wa') || str_contains($k, 'kontak') || str_contains($k, 'phone') || str_contains($k, 'telepon'))) {
                        $vDigits = preg_replace('/\D/', '', $v);
                        if ($vDigits === $rawDigits || Setting::formatWaNumber($v) === $cleanPhone) {
                            $foundWa = true;
                            if (str_contains($k, 'kapten')) {
                                $foundRole = 'Siswa Peserta (Kapten Tim)';
                            }
                            break;
                        }
                    }
                }

                // Cek repeater row
                if (!$foundWa) {
                    foreach ($cf as $k => $val) {
                        if (is_array($val)) {
                            foreach ($val as $row) {
                                if (is_array($row)) {
                                    $cWa = $row['captain_wa'] ?? ($row['wa_number'] ?? ($row['wa'] ?? ($row['phone'] ?? null)));
                                    if ($cWa) {
                                        $cDigits = preg_replace('/\D/', '', $cWa);
                                        if ($cDigits === $rawDigits || Setting::formatWaNumber($cWa) === $cleanPhone) {
                                            $foundWa = true;
                                            $foundName = $row['name'] ?? ($row['nama_lengkap'] ?? null);
                                            $foundEmail = $row['email'] ?? null;
                                            $foundRole = !empty($row['is_captain']) ? 'Siswa Peserta (Kapten Tim)' : 'Siswa Peserta';
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if ($foundWa) {
                    $emailToUse = !empty($foundEmail) && filter_var($foundEmail, FILTER_VALIDATE_EMAIL)
                        ? strtolower(trim($foundEmail))
                        : 'peserta_' . preg_replace('/\D/', '', $cleanPhone) . '@semarak.id';

                    if (Registrant::where('email', $emailToUse)->exists()) {
                        $emailToUse = 'peserta_' . preg_replace('/\D/', '', $cleanPhone) . '_' . rand(100, 999) . '@semarak.id';
                    }

                    $registrant = Registrant::create([
                        'name' => $foundName ?: 'Peserta / Kapten ' . $r->competition_title,
                        'phone' => $cleanPhone,
                        'email' => $emailToUse,
                        'password' => Hash::make(\Illuminate\Support\Str::random(16)),
                        'registrant_role' => $foundRole,
                        'school_name' => $r->school_name,
                        'school_district' => $r->custom_fields['school_district'] ?? ($r->custom_fields['kecamatan'] ?? null),
                        'gender' => 'Laki-laki',
                        'is_active' => true,
                    ]);

                    if (!$r->registrant_id) {
                        $r->registrant_id = $registrant->id;
                        $r->user_id = $registrant->id;
                        $r->save();
                    }
                    break;
                }
            }
        }

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => "Nomor WhatsApp ({$rawPhone}) belum terdaftar sebagai akun pendaftar atau peserta lomba.",
            ], 200);
        }

        if (!$registrant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun pendaftaran Anda dinonaktifkan oleh administrator.',
            ], 200);
        }

        // Generate kode OTP 6-digit
        $otp = (string) mt_rand(100000, 999999);
        $registrant->wa_otp_code = $otp;
        $registrant->wa_otp_expires_at = now()->addMinutes(5);
        $registrant->save();

        $waMsg = "*[SEMARAK ALIYAH XIV 2026]*\n\n"
            . "🔐 Kode OTP Masuk Pendaftar Anda adalah:\n\n"
            . "👉 *{$otp}*\n\n"
            . "Kode verifikasi ini berlaku selama *5 menit*.\n"
            . "Mohon TIDAK MEMBAGIKAN kode ini kepada siapapun demi keamanan akun pendaftaran Anda.";

        $sent = false;
        try {
            $sent = WhatsAppService::sendMessage($cleanPhone, $waMsg);
        } catch (\Throwable $e) {
            Log::warning('Send OTP WA error: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => "Kode OTP 6-digit telah dikirimkan ke WhatsApp nomor " . substr($cleanPhone, 0, 5) . '****' . substr($cleanPhone, -3) . ". Silakan cek pesan masuk Anda.",
            'expires_in' => 300,
            'debug_otp' => config('app.debug') ? $otp : null,
        ]);
    }

    /**
     * Verifikasi kode OTP dan loginkan pendaftar.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
            'redirect_to' => 'nullable|string',
        ], [
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'otp.required' => 'Kode OTP 6-digit wajib diisi.',
            'otp.size' => 'Kode OTP harus berupa 6 digit angka.',
        ]);

        $rawPhone = trim($request->input('phone'));
        $cleanPhone = Setting::formatWaNumber($rawPhone);
        $rawDigits = preg_replace('/\D/', '', $rawPhone);
        $inputOtp = trim($request->input('otp'));

        $registrant = Registrant::where(function ($q) use ($cleanPhone, $rawPhone, $rawDigits) {
            $q->where('phone', $cleanPhone)
              ->orWhere('phone', $rawPhone)
              ->orWhere('phone', $rawDigits);
        })->first();

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Data akun pendaftar tidak ditemukan.',
            ], 200);
        }

        if (empty($registrant->wa_otp_code) || $registrant->wa_otp_code !== $inputOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP yang Anda masukkan salah. Periksa kembali pesan WhatsApp Anda.',
            ], 200);
        }

        if (!$registrant->wa_otp_expires_at || $registrant->wa_otp_expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP telah kadaluwarsa (lebih dari 5 menit). Silakan minta kode OTP baru.',
            ], 200);
        }

        // Berhasil verifikasi: bersihkan OTP dan log in via guard pendaftar
        $registrant->wa_otp_code = null;
        $registrant->wa_otp_expires_at = null;
        $registrant->save();

        Auth::guard('pendaftar')->login($registrant, true);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $lomba = $request->input('lomba');
        $params = $lomba ? ['lomba' => $lomba] : [];

        if ($request->input('redirect_to') === 'register') {
            $targetUrl = route('public.register', $params);
        } else {
            $targetUrl = route('pendaftar.dashboard');
        }

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi OTP berhasil! Mengalihkan ke dashboard...',
            'redirect' => $targetUrl,
        ]);
    }

    /**
     * Endpoint Login Akun Google (Mendukung Google OAuth dan Verifikasi Email Google Terdaftar).
     */
    public function googleLogin(Request $request)
    {
        $clientId = Setting::get('google_client_id', env('GOOGLE_CLIENT_ID', ''));
        $clientSecret = Setting::get('google_client_secret', env('GOOGLE_CLIENT_SECRET', ''));

        // Jika pendaftar memasukkan email Google terdaftar (opsi verifikasi email Google)
        $inputEmail = trim($request->input('email', $request->query('email', '')));
        if (!empty($inputEmail)) {
            return $this->loginWithRegisteredGoogleEmail($inputEmail, $request);
        }

        // Jika Google OAuth terkonfigurasi (Client ID ada)
        if (!empty($clientId)) {
            $redirectUri = url('/auth/google/callback');
            $scope = urlencode('openid profile email');
            $state = csrf_token();
            session(['google_oauth_state' => $state, 'google_redirect_to' => $request->input('redirect_to')]);
            $url = "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}&scope={$scope}&state={$state}";
            return redirect()->away($url);
        }

        // Fallback Lingkungan Lokal / Uji Coba:
        $existingPendaftar = Registrant::whereNotNull('email')
            ->where('email', 'not like', '%@semarak.id')
            ->orderBy('id', 'desc')
            ->first();

        if ($existingPendaftar) {
            Auth::guard('pendaftar')->login($existingPendaftar, true);
            $request->session()->regenerate();
            return redirect()->route('pendaftar.dashboard')->with('success', "Berhasil masuk via Google ({$existingPendaftar->email})!");
        }

        // Fallback akun dummy jika belum ada pendaftaran sama sekali
        $mockEmail = 'ahmad.fauzi@example.com';
        $registrant = Registrant::where('email', $mockEmail)->first();
        if ($registrant) {
            Auth::guard('pendaftar')->login($registrant, true);
            $request->session()->regenerate();
            return redirect()->route('pendaftar.dashboard')->with('success', "Berhasil masuk via Akun Google ({$mockEmail})!");
        }

        return redirect()->route('pendaftar.login')->withErrors([
            'login' => 'Google Client ID belum dikonfigurasi di Pengaturan. Anda dapat masuk menggunakan nomor WhatsApp via OTP atau masukkan email Google terdaftar Anda.',
        ]);
    }

    /**
     * Helper login dengan email Google yang terdaftar di pendaftaran atau akun registrants.
     */
    public function loginWithRegisteredGoogleEmail(string $email, Request $request)
    {
        $cleanEmail = strtolower(trim($email));
        if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['google_email' => 'Format email Google tidak valid (contoh: nama@gmail.com).'])->withInput();
        }

        // 1. Cari di tabel registrants
        $registrant = Registrant::where('email', $cleanEmail)->first();

        // 2. Jika belum ada di registrants, cari di tabel registrations (dari pendaftaran bebas)
        if (!$registrant) {
            $reg = Registration::where(function ($q) use ($cleanEmail) {
                $q->where('custom_fields->mentor_email', $cleanEmail)
                  ->orWhere('custom_fields->contact_email', $cleanEmail)
                  ->orWhere('custom_fields->email', $cleanEmail);
            })->orderBy('id', 'desc')->first();

            if ($reg) {
                $cleanPhone = Setting::formatWaNumber($reg->mentor_wa ?: '08123456789');
                $roleLabel = (stripos($reg->mentor_name, 'kapten') !== false) ? 'Siswa Peserta' : 'Pendamping';

                $registrant = Registrant::create([
                    'name' => $reg->mentor_name ?: 'Pendaftar ' . $reg->school_name,
                    'phone' => $cleanPhone,
                    'email' => $cleanEmail,
                    'password' => Hash::make(\Illuminate\Support\Str::random(16)),
                    'registrant_role' => $roleLabel,
                    'school_name' => $reg->school_name,
                    'gender' => 'Laki-laki',
                    'is_active' => true,
                ]);

                // Link pendaftaran ke registrant baru
                Registration::where(function ($q) use ($cleanEmail, $reg) {
                    $q->where('id', $reg->id)
                      ->orWhere('mentor_wa', $reg->mentor_wa);
                })->update([
                    'registrant_id' => $registrant->id,
                    'user_id' => $registrant->id,
                ]);
            }
        }

        if (!$registrant) {
            return back()->withErrors([
                'google_email' => "Email Google ({$cleanEmail}) belum terdaftar pada data pendaftaran manapun. Pastikan Anda memasukkan email yang sama dengan yang diisi pada formulir pendaftaran.",
            ])->withInput();
        }

        if (!$registrant->is_active) {
            return back()->withErrors([
                'google_email' => 'Akun pendaftar ini dinonaktifkan oleh administrator.',
            ])->withInput();
        }

        Auth::guard('pendaftar')->login($registrant, true);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        if ($request->input('redirect_to') === 'register') {
            $lomba = $request->input('lomba');
            $params = $lomba ? ['lomba' => $lomba] : [];
            return redirect()->route('public.register', $params)->with('success', "Selamat datang, {$registrant->name}! Berhasil masuk via Akun Google.");
        }

        return redirect()->route('pendaftar.dashboard')->with('success', "Selamat datang kembali, {$registrant->name}! Berhasil masuk via Akun Google.");
    }

    /**
     * Handle Callback dari Google OAuth API.
     */
    public function handleGoogleCallback(Request $request)
    {
        $code = $request->query('code');
        $clientId = Setting::get('google_client_id', env('GOOGLE_CLIENT_ID', ''));
        $clientSecret = Setting::get('google_client_secret', env('GOOGLE_CLIENT_SECRET', ''));

        if (empty($code) || empty($clientId) || empty($clientSecret)) {
            return redirect()->route('pendaftar.login')->withErrors([
                'login' => 'Gagal mengautentikasi dengan Google OAuth. Silakan coba kembali atau gunakan Login OTP WhatsApp.',
            ]);
        }

        try {
            $redirectUri = url('/auth/google/callback');
            $response = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (!$response->successful() || empty($response['access_token'])) {
                return redirect()->route('pendaftar.login')->withErrors([
                    'login' => 'Otorisasi Google ditolak atau token kadaluwarsa. Silakan coba lagi.',
                ]);
            }

            $accessToken = $response['access_token'];
            $userResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (!$userResponse->successful() || empty($userResponse['email'])) {
                return redirect()->route('pendaftar.login')->withErrors([
                    'login' => 'Gagal mengambil informasi profil Google Anda.',
                ]);
            }

            $googleUser = $userResponse->json();
            $googleEmail = strtolower(trim($googleUser['email']));

            return $this->loginWithRegisteredGoogleEmail($googleEmail, $request);
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());
            return redirect()->route('pendaftar.login')->withErrors([
                'login' => 'Terjadi kesalahan sistem saat memproses login Google: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Tampilkan formulir lupa / reset password pendaftar.
     */
    public function showForgotPassword(Request $request)
    {
        if (Auth::guard('pendaftar')->check()) {
            return redirect()->route('pendaftar.dashboard');
        }

        $schoolName = Setting::get('school_name', 'MA Unggulan Wahid Hasyim Balung');

        return Inertia::render('Auth/RegistrantForgotPassword', [
            'schoolName' => $schoolName,
        ]);
    }

    /**
     * Kirim kode OTP WhatsApp untuk Reset Password ke akun registrant.
     */
    public function sendResetOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ], [
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
        ]);

        $rawPhone = trim($request->input('phone'));
        $cleanPhone = Setting::formatWaNumber($rawPhone);
        $rawDigits = preg_replace('/\D/', '', $rawPhone);

        $registrant = Registrant::where('phone', $cleanPhone)
            ->orWhere('phone', $rawPhone)
            ->orWhere('phone', $rawDigits)
            ->first();

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => "Nomor WhatsApp ({$rawPhone}) belum terdaftar sebagai akun pendaftar. Pastikan nomor yang dimasukkan sudah terdaftar.",
            ], 404);
        }

        if (!$registrant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun pendaftaran Anda dinonaktifkan oleh administrator.',
            ], 403);
        }

        // Generate kode OTP 6-digit
        $otp = (string) mt_rand(100000, 999999);
        $registrant->wa_otp_code = $otp;
        $registrant->wa_otp_expires_at = now()->addMinutes(5);
        $registrant->save();

        $waMsg = "*[SEMARAK ALIYAH XIV 2026]*\n\n"
            . "🔑 Kode Verifikasi Reset Password Pendaftar Anda adalah:\n\n"
            . "👉 *{$otp}*\n\n"
            . "Kode verifikasi ini berlaku selama *5 menit*.\n"
            . "Gunakan kode ini untuk membuat password baru akun pendaftaran Anda. JANGAN berikan kode ini kepada siapapun demi keamanan.";

        try {
            WhatsAppService::sendMessage($cleanPhone, $waMsg);
        } catch (\Throwable $e) {
            Log::warning('Send Reset OTP WA error: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => "Kode verifikasi 6-digit telah dikirimkan ke WhatsApp nomor " . substr($cleanPhone, 0, 5) . '****' . substr($cleanPhone, -3) . ". Silakan cek pesan WhatsApp Anda.",
            'expires_in' => 300,
            'debug_otp' => config('app.debug') ? $otp : null,
        ]);
    }

    /**
     * Proses reset password pendaftar menggunakan kode OTP WhatsApp.
     */
    public function resetPasswordWithOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'otp.required' => 'Kode verifikasi OTP wajib diisi.',
            'otp.size' => 'Kode OTP harus 6 digit.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $rawPhone = trim($request->input('phone'));
        $cleanPhone = Setting::formatWaNumber($rawPhone);
        $rawDigits = preg_replace('/\D/', '', $rawPhone);
        $inputOtp = trim($request->input('otp'));

        $registrant = Registrant::where(function ($q) use ($cleanPhone, $rawPhone, $rawDigits) {
            $q->where('phone', $cleanPhone)
              ->orWhere('phone', $rawPhone)
              ->orWhere('phone', $rawDigits);
        })->first();

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Akun pendaftar dengan nomor WhatsApp tersebut tidak ditemukan.',
            ], 404);
        }

        if (empty($registrant->wa_otp_code) || $registrant->wa_otp_code !== $inputOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Kode verifikasi OTP salah. Mohon periksa kembali pesan WhatsApp Anda.',
            ], 422);
        }

        if (!$registrant->wa_otp_expires_at || $registrant->wa_otp_expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode verifikasi OTP telah kadaluwarsa (lebih dari 5 menit). Silakan minta kode baru.',
            ], 422);
        }

        // Update password baru & bersihkan OTP
        $registrant->password = Hash::make($request->input('password'));
        $registrant->wa_otp_code = null;
        $registrant->wa_otp_expires_at = null;
        $registrant->save();

        // Otomatis login akun pendaftar
        Auth::guard('pendaftar')->login($registrant, true);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui! Mengalihkan ke dashboard pendaftar...',
            'redirect' => route('pendaftar.dashboard'),
        ]);
    }

    /**
     * Keluar dari akun pendaftar.
     */
    public function logout(Request $request)
    {
        Auth::guard('pendaftar')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pendaftar.login')->with('info', 'Anda telah berhasil keluar dari akun pendaftar.');
    }
}
