<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use App\Models\Setting;
use Illuminate\Console\Command;

class RunBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run 
                            {--format=both : Format cadangan (zip, json, atau both)}
                            {--gdrive : Otomatis sinkronkan hasil cadangan ke Google Drive}
                            {--name= : Label / akhiran nama cadangan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menjalankan pembuatan cadangan aplikasi & database Semarak Aliyah (ZIP / JSON) dan sinkronisasi opsional ke Google Drive';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('======================================================================');
        $this->info('       SEMARAK ALIYAH XIV 2026 - PEMBUATAN CADANGAN OTOMATIS          ');
        $this->info('======================================================================');

        $format = strtolower($this->option('format') ?: 'both');
        $syncGdrive = $this->option('gdrive') || filter_var(Setting::get('backup_gdrive_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
        $name = $this->option('name') ?: 'auto';

        $this->line("• Format Target       : <comment>{$format}</comment>");
        $this->line("• Sinkron Google Drive: " . ($syncGdrive ? '<info>Ya (Aktif)</info>' : '<comment>Tidak</comment>'));
        $this->line("• Waktu Pelaksanaan   : " . now()->format('d/m/Y H:i:s'));

        $createdFiles = [];

        // 1. Cadangan ZIP
        if (in_array($format, ['zip', 'both'])) {
            $this->comment("\n[1] Membuat cadangan ZIP lengkap (Aplikasi + Database)...");
            $zipRes = BackupService::createZipBackup($name);
            if ($zipRes['success']) {
                $this->info("✓ Berhasil membuat ZIP: {$zipRes['filename']} ({$zipRes['size_human']}, {$zipRes['records_count']} baris, {$zipRes['files_count']} berkas)");
                $createdFiles[] = $zipRes['path'];
            } else {
                $this->error("✗ Gagal membuat cadangan ZIP: " . ($zipRes['message'] ?? 'Error tidak diketahui'));
            }
        }

        // 2. Cadangan JSON
        if (in_array($format, ['json', 'both'])) {
            $this->comment("\n[2] Membuat cadangan JSON (Database + Pengaturan Sistem)...");
            $jsonRes = BackupService::createJsonBackup($name);
            if ($jsonRes['success']) {
                $this->info("✓ Berhasil membuat JSON: {$jsonRes['filename']} ({$jsonRes['size_human']}, {$jsonRes['records_count']} baris)");
                $createdFiles[] = $jsonRes['path'];
            } else {
                $this->error("✗ Gagal membuat cadangan JSON: " . ($jsonRes['message'] ?? 'Error tidak diketahui'));
            }
        }

        // 3. Sinkronisasi Google Drive
        if ($syncGdrive && !empty($createdFiles)) {
            $this->comment("\n[3] Menyinkronkan berkas cadangan ke Google Drive...");
            foreach ($createdFiles as $filePath) {
                $base = basename($filePath);
                $this->line("  Mengunggah {$base}...");
                $gdRes = BackupService::uploadToGoogleDrive($filePath);
                if ($gdRes['success']) {
                    $this->info("  ✓ {$base} berhasil diunggah ke Google Drive (ID: {$gdRes['file_id']})");
                } else {
                    $this->warn("  ! Gagal unggah {$base}: " . ($gdRes['message'] ?? ''));
                }
            }
        }

        Setting::set('backup_last_run_at', now()->toDateTimeString());
        Setting::set('backup_last_status', 'success');
        Setting::set('backup_last_message', 'Cadangan selesai: ' . count($createdFiles) . ' berkas berhasil dibuat.');

        $this->info("\n======================================================================");
        $this->info('       PROSES PEMBUATAN CADANGAN TELAH SELESAI DENGAN SUKSES!        ');
        $this->info('======================================================================');

        return Command::SUCCESS;
    }
}
