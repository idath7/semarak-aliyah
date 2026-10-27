<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\SchoolController;

// Public Endpoints
Route::get('/competitions', [CompetitionController::class, 'index']);
Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:15,1');

// Database Sekolah & Madrasah (SMP/MTs) Kabupaten Jember Public APIs
Route::prefix('schools')->group(function () {
    Route::get('/', [SchoolController::class, 'index']);
    Route::get('/kecamatan', [SchoolController::class, 'kecamatan']);
    Route::get('/stats', [SchoolController::class, 'stats']);
    Route::get('/check-status', [SchoolController::class, 'checkRegistrationStatus']);
    Route::get('/verify-package-code', [SchoolController::class, 'verifyPackageCode']);
    Route::post('/public-register', [SchoolController::class, 'publicRegisterSchool']);
    Route::get('/{id}', [SchoolController::class, 'show']);
});

// Admin Panel Endpoints (Protected with web auth & admin role)
Route::middleware(['web', 'auth:web', 'admin'])->prefix('admin')->group(function () {
    Route::get('/overview', [AdminController::class, 'overview']);
    Route::post('/verify', [AdminController::class, 'verify']);
    Route::post('/toggle-quota', [AdminController::class, 'toggleQuota']);
    Route::get('/export', [AdminController::class, 'export']);

    // Admin Sekolah Management APIs
    Route::post('/schools', [SchoolController::class, 'store']);
    Route::put('/schools/{id}', [SchoolController::class, 'update']);
    Route::delete('/schools/{id}', [SchoolController::class, 'destroy']);
    Route::post('/schools/sync-defaults', [SchoolController::class, 'syncDefaults']);
});

// Operational Command Center APIs (Protected with web auth & admin role)
Route::middleware(['web', 'auth:web', 'admin'])->prefix('operasional')->group(function () {
    Route::get('/overview', [\App\Http\Controllers\OperasionalHubController::class, 'overview']);
    Route::get('/delegates', [\App\Http\Controllers\OperasionalHubController::class, 'delegates']);
    Route::post('/gate/scan', [\App\Http\Controllers\OperasionalHubController::class, 'gateScan']);
    Route::post('/gate/undo/{id}', [\App\Http\Controllers\OperasionalHubController::class, 'gateUndo']);
    Route::post('/verification/{id}', [\App\Http\Controllers\OperasionalHubController::class, 'verificationUpdate']);
    Route::get('/jury/{compId}', [\App\Http\Controllers\OperasionalHubController::class, 'juryData']);
    Route::post('/jury/score', [\App\Http\Controllers\OperasionalHubController::class, 'juryScore']);
    Route::get('/jury/rubric/{compId}', [\App\Http\Controllers\OperasionalHubController::class, 'getRubric']);
    Route::post('/jury/rubric/{compId}', [\App\Http\Controllers\OperasionalHubController::class, 'updateRubric']);
    Route::post('/jury/rubric/{compId}/reset', [\App\Http\Controllers\OperasionalHubController::class, 'resetRubric']);
    Route::get('/tournament/{compId}', [\App\Http\Controllers\OperasionalHubController::class, 'tournamentData']);
    Route::post('/tournament/match/{matchId}/score', [\App\Http\Controllers\OperasionalHubController::class, 'tournamentMatchScore']);
    Route::post('/tournament/generate/{compId}', [\App\Http\Controllers\OperasionalHubController::class, 'tournamentGenerate']);
    Route::get('/id-cards', [\App\Http\Controllers\OperasionalHubController::class, 'idCards']);
});
