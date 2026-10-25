<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\GateCheckinController;
use App\Http\Controllers\Admin\FileVerificationController;
use App\Http\Controllers\Admin\JuryScoringController;
use App\Http\Controllers\Admin\PrintStudioController;
use App\Http\Controllers\Portal\LiveScoreboardController;
use App\Http\Controllers\PublicPortalController;

// Public Landing Page & Registration Portal (Inertia Vue 3)
Route::get('/', [PublicPortalController::class, 'home'])->name('public.home');
Route::get('/layanan-registrasi', [PublicPortalController::class, 'register'])->name('public.register');
Route::post('/layanan-registrasi', [PublicPortalController::class, 'storeRegistration'])->middleware('throttle:15,1')->name('public.register.store');

// Public Stage Live Scoreboard (Accessible for stage projector)
Route::get('/scoreboard', [LiveScoreboardController::class, 'index'])->name('public.scoreboard');
Route::get('/portal/live-scoreboard', [LiveScoreboardController::class, 'index'])->name('portal.scoreboard');

// Operational Command Center (Pusat Komando Lapangan Hari-H - Dark UI) - Protected with Auth & Admin Role
Route::middleware(['auth:web', 'admin'])->group(function () {
    Route::get('/operasional', [\App\Http\Controllers\OperasionalHubController::class, 'index'])->name('operasional');
    Route::get('/operasional/turnamen', function () { return redirect('/operasional?tab=turnamen'); });
    Route::get('/operasional/turnamen/{comp}', function ($comp) { return redirect("/operasional?tab=turnamen&comp={$comp}"); });
    Route::get('/operasional/gate', function () { return redirect('/operasional?tab=gate'); });
    Route::get('/operasional/juri', function () { return redirect('/operasional?tab=juri'); });
    Route::get('/operasional/juri/{comp}', function ($comp) { return redirect("/operasional?tab=juri&comp={$comp}"); });
    Route::get('/operasional/verifikasi', function () { return redirect('/operasional?tab=berkas'); });
    Route::get('/operasional/print', function () { return redirect('/operasional?tab=print'); });
});

// Authentication Routes (Rate limited against brute-force)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:15,1')->name('login.post');
Route::post('/login/sso-sipresensi', [AuthController::class, 'loginSsoSipresensi'])->middleware('throttle:15,1')->name('login.sso');
Route::post('/login/wa-otp/send', [AuthController::class, 'sendWaOtp'])->middleware('throttle:10,1')->name('login.otp.send');
Route::post('/login/wa-otp/verify', [AuthController::class, 'verifyWaOtp'])->middleware('throttle:15,1')->name('login.otp.verify');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Google OAuth & Direct Login untuk Admin / Panitia
Route::get('/admin/auth/google', [AuthController::class, 'adminGoogleLogin'])->name('admin.auth.google');
Route::post('/admin/auth/google/direct', [AuthController::class, 'adminGoogleDirectLogin'])->name('admin.auth.google.direct');
Route::get('/admin/auth/google/callback', [AuthController::class, 'handleAdminGoogleCallback'])->name('admin.auth.google.callback');

// =========================================================================
// PORTAL & OTENTIKASI AKUN PENDAFTAR
// =========================================================================
Route::prefix('pendaftar')->name('pendaftar.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::get('/register', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'register'])->middleware('throttle:10,1')->name('register.post');
    Route::post('/send-otp', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'sendOtp'])->middleware('throttle:6,1')->name('send-otp');
    Route::post('/verify-otp', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('verify-otp');
    Route::get('/lupa-password', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/lupa-password/send-otp', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'sendResetOtp'])->middleware('throttle:6,1')->name('forgot-password.send-otp');
    Route::post('/lupa-password/reset', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'resetPasswordWithOtp'])->middleware('throttle:10,1')->name('forgot-password.reset');
    Route::post('/logout', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'logout'])->name('logout');

    // Protected Registrant Portal Routes
    Route::middleware(['auth:pendaftar', 'registrant'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Registrant\RegistrantDashboardController::class, 'index'])->name('dashboard');
        Route::get('/daftar-lomba', [\App\Http\Controllers\Registrant\RegistrantDashboardController::class, 'createRegistration'])->name('registrations.create');
        Route::post('/daftar-lomba', [\App\Http\Controllers\Registrant\RegistrantDashboardController::class, 'storeRegistration'])->name('registrations.store');
        Route::post('/registrations/{id}/update', [\App\Http\Controllers\Registrant\RegistrantDashboardController::class, 'updateRegistration'])->name('registrations.update');
        Route::post('/registrations/{id}/assign-pic', [\App\Http\Controllers\Registrant\RegistrantDashboardController::class, 'assignPic'])->name('registrations.assign-pic');
        Route::post('/registrations/{id}/remove-pic', [\App\Http\Controllers\Registrant\RegistrantDashboardController::class, 'removePic'])->name('registrations.remove-pic');
        Route::post('/paket/all-in/activate', [\App\Http\Controllers\Registrant\RegistrantDashboardController::class, 'activateAllInPackage'])->name('paket.all-in.activate');
        Route::post('/paket/all-in/upload-proof', [\App\Http\Controllers\Registrant\RegistrantDashboardController::class, 'uploadPackageProof'])->name('paket.all-in.upload-proof');
        Route::post('/profile/update', [\App\Http\Controllers\Registrant\RegistrantDashboardController::class, 'updateProfile'])->name('profile.update');
    });
});

// Google OAuth & Registered Email Login
Route::get('/auth/google', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'googleLogin'])->name('auth.google');
Route::post('/auth/google', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'googleLogin'])->name('auth.google.post');
Route::get('/auth/google/callback', [\App\Http\Controllers\Registrant\RegistrantAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Protected Admin Panel Routes (Velzon Theme for Registrations & Quota)
Route::middleware(['auth:web', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // 1. Dashboard & Management Pages (Velzon Theme)
    Route::get('/', [AdminPanelController::class, 'index'])->name('dashboard');
    Route::get('/buat-password', [AuthController::class, 'showFirstTimePassword'])->name('password.first');
    Route::post('/buat-password', [AuthController::class, 'storeFirstTimePassword'])->name('password.first.store');
    Route::get('/pendaftar', [AdminPanelController::class, 'pendaftar'])->name('pendaftar');
    Route::post('/pendaftar/manual-store', [AdminPanelController::class, 'storeManualRegistration'])->name('pendaftar.manual-store');
    Route::post('/pendaftar/merge-schools', [AdminPanelController::class, 'mergeSchools'])->name('pendaftar.merge-schools');
    Route::get('/sekolah-terdaftar', [AdminPanelController::class, 'registeredSchoolsIndex'])->name('sekolah-terdaftar.index');
    Route::get('/pendaftar/{id}', [AdminPanelController::class, 'pendaftarDetail'])->name('pendaftar.detail');
    Route::post('/pendaftar/{id}/verify', [AdminPanelController::class, 'verify'])->name('pendaftar.verify');
    Route::get('/lomba', [AdminPanelController::class, 'competitionsIndex'])->name('competitions.index');
    Route::post('/lomba', [AdminPanelController::class, 'storeCompetition'])->name('competitions.store');
    Route::put('/lomba/{id}', [AdminPanelController::class, 'updateCompetition'])->name('competitions.update');
    Route::delete('/lomba/{id}', [AdminPanelController::class, 'destroyCompetition'])->name('competitions.destroy');
    Route::post('/kategori', [AdminPanelController::class, 'storeCategory'])->name('categories.store');
    Route::delete('/kategori/{id}', [AdminPanelController::class, 'destroyCategory'])->name('categories.destroy');
    Route::post('/verify/{id}', [AdminPanelController::class, 'verify'])->name('verify');
    Route::post('/toggle-quota/{id}', [AdminPanelController::class, 'toggleQuota'])->name('toggle-quota');
    Route::get('/export', [AdminPanelController::class, 'export'])->name('export');

    // Manajemen Pengguna & Panitia (Users & Committee Management)
    Route::get('/pengguna', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::post('/pengguna', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::get('/pengguna/sipresensi/preview', [\App\Http\Controllers\Admin\UserController::class, 'previewSipresensiTeachers'])->name('users.sipresensi.preview');
    Route::post('/pengguna/sipresensi/sync', [\App\Http\Controllers\Admin\UserController::class, 'syncSipresensiTeachers'])->name('users.sipresensi.sync');
    Route::put('/pengguna/{id}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::post('/pengguna/{id}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('/pengguna/{id}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');

    // Manajemen Hak Akses & Matriks Peran Panitia (Role & Permission Management)
    Route::prefix('pengguna/hak-akses')->name('roles.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\RolePermissionController::class, 'index'])->name('index');
        Route::post('/matrix', [\App\Http\Controllers\Admin\RolePermissionController::class, 'updateMatrix'])->name('matrix.update');
        Route::post('/role', [\App\Http\Controllers\Admin\RolePermissionController::class, 'storeRole'])->name('store');
        Route::put('/role/{id}', [\App\Http\Controllers\Admin\RolePermissionController::class, 'updateRole'])->name('update');
        Route::delete('/role/{id}', [\App\Http\Controllers\Admin\RolePermissionController::class, 'destroyRole'])->name('destroy');
        Route::post('/reset-defaults', [\App\Http\Controllers\Admin\RolePermissionController::class, 'resetDefaults'])->name('reset-defaults');
        Route::post('/user/{userId}/custom-permissions', [\App\Http\Controllers\Admin\RolePermissionController::class, 'updateUserPermissions'])->name('user-permissions.update');
    });

    // Pintasan Langsung /admin/hak-akses
    Route::get('/hak-akses', function () {
        return redirect()->route('admin.roles.index');
    })->name('roles.shortcut');

    // Database Master Sekolah & Madrasah (SMP/MTs) di Kabupaten Jember
    Route::get('/sekolah', [\App\Http\Controllers\Admin\SchoolController::class, 'index'])->name('schools.index');
    Route::post('/sekolah', [\App\Http\Controllers\Admin\SchoolController::class, 'store'])->name('schools.store');
    Route::put('/sekolah/{id}', [\App\Http\Controllers\Admin\SchoolController::class, 'update'])->name('schools.update');
    Route::delete('/sekolah/{id}', [\App\Http\Controllers\Admin\SchoolController::class, 'destroy'])->name('schools.destroy');
    Route::post('/sekolah/sync-defaults', [\App\Http\Controllers\Admin\SchoolController::class, 'syncDefaults'])->name('schools.sync-defaults');
    Route::post('/sekolah/sync-registrations', [\App\Http\Controllers\Admin\SchoolController::class, 'syncRegistrations'])->name('schools.sync-registrations');
    Route::get('/sekolah/export-csv', [\App\Http\Controllers\Admin\SchoolController::class, 'exportCsv'])->name('schools.export-csv');
    Route::get('/sekolah/template-csv', [\App\Http\Controllers\Admin\SchoolController::class, 'templateCsv'])->name('schools.template-csv');
    Route::post('/sekolah/import-csv', [\App\Http\Controllers\Admin\SchoolController::class, 'importCsv'])->name('schools.import-csv');
    Route::post('/sekolah/{id}/toggle-active', [\App\Http\Controllers\Admin\SchoolController::class, 'toggleActive'])->name('schools.toggle-active');
    Route::post('/sekolah/clean-invalid', [\App\Http\Controllers\Admin\SchoolController::class, 'cleanInvalid'])->name('schools.clean-invalid');
    Route::post('/sekolah/clear-all', [\App\Http\Controllers\Admin\SchoolController::class, 'clearAll'])->name('schools.clear-all');
    Route::post('/sekolah/bulk-delete', [\App\Http\Controllers\Admin\SchoolController::class, 'bulkDelete'])->name('schools.bulk-delete');

    // Manajemen Kelola Biaya Registrasi & Paket Pembayaran
    Route::get('/biaya', [AdminPanelController::class, 'biayaIndex'])->name('biaya.index');
    Route::post('/biaya/competition/{id}', [AdminPanelController::class, 'updateCompetitionFee'])->name('biaya.competition.update');
    Route::post('/biaya/batch-competition', [AdminPanelController::class, 'batchUpdateCompetitionFee'])->name('biaya.competition.batch');
    Route::post('/biaya/package', [AdminPanelController::class, 'updatePackageSettings'])->name('biaya.package.update');
    Route::post('/biaya/payment', [AdminPanelController::class, 'updatePaymentSettings'])->name('biaya.payment.update');

    // Manajemen Technical Meeting (TM) & Pengundian Nomor Urut
    Route::get('/tm', [AdminPanelController::class, 'tmIndex'])->name('tm.index');
    Route::post('/tm-settings', [AdminPanelController::class, 'updateTmSectionSettings'])->name('tm.section-settings');
    Route::post('/tm/{competitionId}', [AdminPanelController::class, 'updateTmSettings'])->name('tm.update');
    Route::post('/tm/{competitionId}/draw', [AdminPanelController::class, 'randomizeDraw'])->name('tm.draw');
    Route::post('/tm/{competitionId}/order', [AdminPanelController::class, 'updateParticipantOrder'])->name('tm.order');
    Route::post('/tm/{competitionId}/reset', [AdminPanelController::class, 'resetDraw'])->name('tm.reset');

    // Manajemen Buku Panduan Juknis (Juknis Umum & Spesifik Cabang)
    Route::get('/juknis', [AdminPanelController::class, 'juknisIndex'])->name('juknis.index');
    Route::post('/juknis/general', [AdminPanelController::class, 'updateGeneralJuknis'])->name('juknis.general.update');
    Route::post('/juknis/general/reset', [AdminPanelController::class, 'resetGeneralJuknis'])->name('juknis.general.reset');
    Route::post('/juknis/competition/{id}', [AdminPanelController::class, 'updateCompetitionJuknis'])->name('juknis.competition.update');
    Route::delete('/juknis/competition/{id}', [AdminPanelController::class, 'deleteCompetitionJuknis'])->name('juknis.competition.delete');

    // Form Builder untuk Pendaftaran Cabang Lomba
    Route::get('/form-builder', [AdminPanelController::class, 'formBuilderIndex'])->name('form-builder.index');
    Route::post('/form-builder/presets/save', [AdminPanelController::class, 'formBuilderSavePreset'])->name('form-builder.presets.save');
    Route::post('/form-builder/presets/delete', [AdminPanelController::class, 'formBuilderDeletePreset'])->name('form-builder.presets.delete');
    Route::post('/form-builder/presets/reset-default', [AdminPanelController::class, 'formBuilderResetPresets'])->name('form-builder.presets.reset');
    Route::get('/form-builder/{competitionId}', [AdminPanelController::class, 'formBuilderShow'])->name('form-builder.show');
    Route::post('/form-builder/{competitionId}', [AdminPanelController::class, 'formBuilderSave'])->name('form-builder.save');
    Route::post('/form-builder/{competitionId}/preset', [AdminPanelController::class, 'formBuilderApplyPreset'])->name('form-builder.preset');
    Route::post('/form-builder/{competitionId}/copy', [AdminPanelController::class, 'formBuilderCopyFrom'])->name('form-builder.copy');
    Route::post('/form-builder/{competitionId}/reset', [AdminPanelController::class, 'formBuilderReset'])->name('form-builder.reset');

    // Manajemen Mitra & Sponsor Resmi Slider
    Route::get('/sponsor', [AdminPanelController::class, 'sponsorIndex'])->name('sponsor.index');
    Route::post('/sponsor', [AdminPanelController::class, 'storeSponsor'])->name('sponsor.store');
    Route::post('/sponsor/{id}', [AdminPanelController::class, 'updateSponsor'])->name('sponsor.update');
    Route::delete('/sponsor/{id}', [AdminPanelController::class, 'destroySponsor'])->name('sponsor.destroy');
    Route::post('/sponsor-toggle/{id}', [AdminPanelController::class, 'toggleSponsorActive'])->name('sponsor.toggle');
    Route::post('/sponsor-reorder', [AdminPanelController::class, 'reorderSponsors'])->name('sponsor.reorder');
    Route::post('/sponsor-settings', [AdminPanelController::class, 'updateSponsorSettings'])->name('sponsor.settings');

    // Manajemen Galeri Dokumentasi 2-Baris Counter-Slider
    Route::get('/galeri', [AdminPanelController::class, 'galleryIndex'])->name('gallery.index');
    Route::post('/galeri', [AdminPanelController::class, 'storeGallery'])->name('gallery.store');
    Route::post('/galeri/{id}', [AdminPanelController::class, 'updateGallery'])->name('gallery.update');
    Route::delete('/galeri/{id}', [AdminPanelController::class, 'destroyGallery'])->name('gallery.destroy');
    Route::post('/galeri-toggle/{id}', [AdminPanelController::class, 'toggleGalleryActive'])->name('gallery.toggle');
    Route::post('/galeri-reorder', [AdminPanelController::class, 'reorderGalleries'])->name('gallery.reorder');
    Route::post('/galeri-settings', [AdminPanelController::class, 'updateGallerySettings'])->name('gallery.settings');

    // Manajemen Tanya Jawab (FAQ)
    Route::get('/faq', [AdminPanelController::class, 'faqIndex'])->name('faq.index');
    Route::post('/faq', [AdminPanelController::class, 'storeFaq'])->name('faq.store');
    Route::post('/faq/{id}', [AdminPanelController::class, 'updateFaq'])->name('faq.update');
    Route::delete('/faq/{id}', [AdminPanelController::class, 'destroyFaq'])->name('faq.destroy');
    Route::post('/faq-toggle/{id}', [AdminPanelController::class, 'toggleFaqActive'])->name('faq.toggle');
    Route::post('/faq-reorder', [AdminPanelController::class, 'reorderFaqs'])->name('faq.reorder');
    Route::post('/faq-settings', [AdminPanelController::class, 'updateFaqSettings'])->name('faq.settings');

    // Manajemen Kustomisasi Lanjutan Web Publik (Suite Layout: Efek & Interaksi, Teks Berjalan, Ekstensibilitas)
    Route::prefix('kustomisasi')->name('kustomisasi.')->group(function () {
        Route::get('/', [AdminPanelController::class, 'kustomisasiIndex'])->name('index');
        Route::post('/', [AdminPanelController::class, 'updateKustomisasi'])->name('update');
        Route::post('/reset', [AdminPanelController::class, 'resetKustomisasi'])->name('reset');
        Route::get('/teks-berjalan', [AdminPanelController::class, 'tickerIndex'])->name('ticker');
    });

    // Backward-compatible redirect & endpoint for Ticker
    Route::get('/ticker', function () {
        return redirect()->route('kustomisasi.ticker');
    })->name('ticker.index');
    Route::post('/ticker-settings', [AdminPanelController::class, 'updateTickerSettings'])->name('ticker.settings');

    // =========================================================================
    // FITUR PENGATURAN SISTEM & WEB (PROFIL SEKOLAH, MODE PERAWATAN, SEO OG)
    // =========================================================================
    Route::prefix('pengaturan')->name('settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('index');
        
        // 1. Konfigurasi Profil Sekolah
        Route::get('/profil-sekolah', [\App\Http\Controllers\Admin\SettingsController::class, 'schoolProfile'])->name('school-profile');
        Route::post('/profil-sekolah', [\App\Http\Controllers\Admin\SettingsController::class, 'updateSchoolProfile'])->name('school-profile.update');

        // 2. Setting Mode Perawatan Web
        Route::get('/mode-perawatan', [\App\Http\Controllers\Admin\SettingsController::class, 'maintenanceMode'])->name('maintenance');
        Route::post('/mode-perawatan', [\App\Http\Controllers\Admin\SettingsController::class, 'updateMaintenanceMode'])->name('maintenance.update');

        // 3. Setting SEO & Open Graph Web
        Route::get('/seo-og', [\App\Http\Controllers\Admin\SettingsController::class, 'seoOg'])->name('seo-og');
        Route::post('/seo-og', [\App\Http\Controllers\Admin\SettingsController::class, 'updateSeoOg'])->name('seo-og.update');

        // 4. Quick Update Master Edisi & Tahun Event
        Route::post('/update-edisi', [\App\Http\Controllers\Admin\SettingsController::class, 'updateEdition'])->name('edition.update');

        // 5. Fitur WhatsApp Gateway (OpenWA Production)
        Route::get('/wa-gateway', [\App\Http\Controllers\Admin\SettingsController::class, 'waGateway'])->name('wa-gateway');
        Route::get('/wa-gateway/sessions', [\App\Http\Controllers\Admin\SettingsController::class, 'fetchWaSessions'])->name('wa-gateway.sessions');
        Route::get('/wa-gateway/groups', [\App\Http\Controllers\Admin\SettingsController::class, 'fetchWaGroups'])->name('wa-gateway.groups');
        Route::post('/wa-gateway', [\App\Http\Controllers\Admin\SettingsController::class, 'updateWaGateway'])->name('wa-gateway.update');
        Route::post('/wa-gateway/test-connection', [\App\Http\Controllers\Admin\SettingsController::class, 'testWaConnection'])->name('wa-gateway.test-connection');
        Route::post('/wa-gateway/send-test', [\App\Http\Controllers\Admin\SettingsController::class, 'sendTestWaMessage'])->name('wa-gateway.send-test');
        Route::post('/wa-gateway/resend/{id}', [\App\Http\Controllers\Admin\SettingsController::class, 'resendWaLog'])->name('wa-gateway.resend');
        Route::post('/wa-gateway/clear-logs', [\App\Http\Controllers\Admin\SettingsController::class, 'clearWaLogs'])->name('wa-gateway.clear-logs');
        Route::post('/wa-gateway/logs/{id}/delete', [\App\Http\Controllers\Admin\SettingsController::class, 'deleteWaLog'])->name('wa-gateway.logs.delete');
        Route::post('/wa-gateway/send-manual', [\App\Http\Controllers\Admin\SettingsController::class, 'sendManualRegistrantWa'])->name('wa-gateway.send-manual');

        // 6. Kebijakan Pendaftaran & Akun Pendaftar
        Route::get('/kebijakan-pendaftaran', [\App\Http\Controllers\Admin\SettingsController::class, 'registrationPolicy'])->name('registration-policy');
        Route::post('/kebijakan-pendaftaran', [\App\Http\Controllers\Admin\SettingsController::class, 'updateRegistrationPolicy'])->name('registration-policy.update');
        Route::post('/kebijakan-pendaftaran/toggle-login', [\App\Http\Controllers\Admin\SettingsController::class, 'toggleRegistrationLoginRequired'])->name('registration-policy.toggle-login');

        // 7. Kustomisasi Gambar & Tampilan Login Admin
        Route::get('/login-admin', [\App\Http\Controllers\Admin\SettingsController::class, 'adminLoginCustomization'])->name('login-admin');
        Route::post('/login-admin', [\App\Http\Controllers\Admin\SettingsController::class, 'updateAdminLoginCustomization'])->name('login-admin.update');
        Route::post('/login-admin/reset', [\App\Http\Controllers\Admin\SettingsController::class, 'resetAdminLoginCustomization'])->name('login-admin.reset');

        // 8. Konfigurasi API Sipresensi (SSO & Integrasi Guru)
        Route::get('/sipresensi', [\App\Http\Controllers\Admin\SettingsController::class, 'sipresensiSettings'])->name('sipresensi');
        Route::post('/sipresensi', [\App\Http\Controllers\Admin\SettingsController::class, 'updateSipresensiSettings'])->name('sipresensi.update');
        Route::post('/sipresensi/test-connection', [\App\Http\Controllers\Admin\SettingsController::class, 'testSipresensiConnection'])->name('sipresensi.test-connection');
        Route::post('/sipresensi/test-sync', [\App\Http\Controllers\Admin\SettingsController::class, 'testSipresensiSync'])->name('sipresensi.test-sync');

        // 9. Informasi Sistem & Kinerja Server
        Route::get('/informasi-sistem', [\App\Http\Controllers\Admin\SettingsController::class, 'systemInformation'])->name('system-info');
        Route::post('/informasi-sistem/clear-cache', [\App\Http\Controllers\Admin\SettingsController::class, 'clearSystemCache'])->name('system-info.clear-cache');
    });

    // 2. Redirect all operational routes to Dark Operational UI
    Route::get('/operasional', function () { return redirect('/operasional'); })->name('operasional');
    Route::get('/gate-checkin', function () { return redirect('/operasional?tab=gate'); })->name('gate.index');
    Route::post('/gate-checkin/scan', [GateCheckinController::class, 'scan'])->name('gate.scan');
    Route::post('/gate-checkin/undo/{id}', [GateCheckinController::class, 'undo'])->name('gate.undo');

    Route::get('/verification', function () { return redirect('/operasional?tab=berkas'); })->name('verification.index');
    Route::post('/verification/status/{id}', [FileVerificationController::class, 'updateStatus'])->name('verification.status');

    Route::get('/jury', function () { return redirect('/operasional?tab=juri'); })->name('jury.index');
    Route::get('/jury/arena/{competitionId}', function ($competitionId) { return redirect("/operasional?tab=juri&comp={$competitionId}"); })->name('jury.arena');
    Route::post('/jury/score', [JuryScoringController::class, 'storeScore'])->name('jury.score');
    Route::get('/jury/rubric/{competitionId}', [JuryScoringController::class, 'getRubric'])->name('jury.rubric.get');
    Route::post('/jury/rubric/{competitionId}', [JuryScoringController::class, 'updateRubric'])->name('jury.rubric.update');
    Route::post('/jury/rubric/{competitionId}/reset', [JuryScoringController::class, 'resetRubric'])->name('jury.rubric.reset');

    Route::get('/tournament/arena/{competitionId}', function ($competitionId) { return redirect("/operasional?tab=turnamen&comp={$competitionId}"); })->name('tournament.arena');
    Route::post('/tournament/match/{matchId}/score', [\App\Http\Controllers\Admin\TournamentMatchController::class, 'updateScore'])->name('tournament.match.score');
    Route::post('/tournament/match/{matchId}/teams', [\App\Http\Controllers\Admin\TournamentMatchController::class, 'setTeams'])->name('tournament.match.teams');
    Route::post('/tournament/bracket/{competitionId}/generate', [\App\Http\Controllers\Admin\TournamentMatchController::class, 'generateBracket'])->name('tournament.bracket.generate');

    Route::get('/print-studio', function () { return redirect('/operasional?tab=print'); })->name('print.index');
    Route::get('/print-studio/id-cards', [PrintStudioController::class, 'printIdCards'])->name('print.idcards');
    Route::get('/print-studio/certificate/{id}', [PrintStudioController::class, 'printCertificate'])->name('print.certificate');
});
