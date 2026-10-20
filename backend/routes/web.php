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

// Operational Command Center (Pusat Komando Lapangan Hari-H - Dark UI) - Protected with Auth
Route::middleware('auth')->group(function () {
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
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Admin Panel Routes (Velzon Theme for Registrations & Quota)
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    // 1. Dashboard & Management Pages (Velzon Theme)
    Route::get('/', [AdminPanelController::class, 'index'])->name('dashboard');
    Route::get('/pendaftar', [AdminPanelController::class, 'pendaftar'])->name('pendaftar');
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
    Route::put('/pengguna/{id}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::post('/pengguna/{id}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('/pengguna/{id}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');

    // Database Master Sekolah & Madrasah (SMP/MTs) di Kabupaten Jember
    Route::get('/sekolah', [\App\Http\Controllers\Admin\SchoolController::class, 'index'])->name('schools.index');
    Route::post('/sekolah', [\App\Http\Controllers\Admin\SchoolController::class, 'store'])->name('schools.store');
    Route::put('/sekolah/{id}', [\App\Http\Controllers\Admin\SchoolController::class, 'update'])->name('schools.update');
    Route::delete('/sekolah/{id}', [\App\Http\Controllers\Admin\SchoolController::class, 'destroy'])->name('schools.destroy');
    Route::post('/sekolah/sync-defaults', [\App\Http\Controllers\Admin\SchoolController::class, 'syncDefaults'])->name('schools.sync-defaults');
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

    // Form Builder untuk Pendaftaran Cabang Lomba
    Route::get('/form-builder', [AdminPanelController::class, 'formBuilderIndex'])->name('form-builder.index');
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
