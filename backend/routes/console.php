<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Fitur Pembaruan Otomatis Aplikasi (Docker Hot Update)
|--------------------------------------------------------------------------
| Perintah ini digunakan saat terdapat perbaikan kode, penambahan fitur baru,
| atau migrasi database tanpa perlu mematikan container.
*/
Artisan::command('app:update {--no-cache : Jangan optimasi cache produksi}', function () {
    $this->info('======================================================================');
    $this->info('       MEMULAI PEMBARUAN OTOMATIS APLIKASI SEMARAK ALIYAH XIV         ');
    $this->info('======================================================================');

    // 1. Storage Symlink
    $this->comment('[1/5] Memastikan link penyimpanan publik (storage link)...');
    $this->call('storage:link');

    // 2. Database Migrations
    $this->comment('[2/5] Menjalankan migrasi database terbaru...');
    $this->call('migrate', ['--force' => true]);

    // 3. Clear Cache
    $this->comment('[3/5] Membersihkan cache lama aplikasi...');
    $this->call('optimize:clear');

    // 4. Production Cache Optimization
    if (! $this->option('no-cache')) {
        $this->comment('[4/5] Mengompilasi dan mengoptimasi cache konfigurasi, route, dan view...');
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');
    } else {
        $this->comment('[4/5] Mode development: Melewati kompilasi cache.');
    }

    // 5. Reload Octane Workers (Zero Downtime)
    $this->comment('[5/5] Me-reload worker FrankenPHP Octane (Zero-Downtime Hot Reload)...');
    try {
        $this->call('octane:reload');
        $this->info('✓ Worker Octane berhasil dimuat ulang!');
    } catch (\Throwable $e) {
        $this->warn('! Peringatan saat me-reload Octane: ' . $e->getMessage());
    }

    $this->info('======================================================================');
    $this->info('       PEMBARUAN APLIKASI SELESAI DENGAN SUKSES!                      ');
    $this->info('======================================================================');
})->purpose('Menjalankan pembaruan aplikasi otomatis: migrasi, optimasi cache, dan reload worker Octane');

Artisan::command('competitions:sync-mandatory-fields', function () {
    $this->info('Menyinkronkan 8 Field Identitas & Paket Standar ke seluruh cabang lomba...');
    $competitions = \App\Models\Competition::all();
    $updatedCount = 0;

    foreach ($competitions as $comp) {
        $existing = $comp->custom_fields ?? [];
        $synced = \App\Http\Controllers\AdminPanelController::ensureMandatoryFields($existing);
        $comp->custom_fields = $synced;
        $comp->save();
        $this->line("✓ Cabang [{$comp->id}] {$comp->title}: " . count($synced) . " field.");
        $updatedCount++;
    }

    $this->info("Berhasil menyinkronkan {$updatedCount} cabang lomba dengan 8 field identitas & paket standar!");
})->purpose('Sinkronisasi 8 field standar (Sekolah, Kecamatan, NPSN, Alamat, Pendamping, WA, Email, Kode Paket Komplit) ke seluruh cabang lomba');

