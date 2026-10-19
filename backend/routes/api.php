<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\AdminController;

// Public Endpoints
Route::get('/competitions', [CompetitionController::class, 'index']);
Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:15,1');

// Admin Panel Endpoints (Protected with web auth)
Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {
    Route::get('/overview', [AdminController::class, 'overview']);
    Route::post('/verify', [AdminController::class, 'verify']);
    Route::post('/toggle-quota', [AdminController::class, 'toggleQuota']);
    Route::get('/export', [AdminController::class, 'export']);
});

// Operational Command Center APIs (Protected with web auth)
Route::middleware(['web', 'auth'])->prefix('operasional')->group(function () {
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
