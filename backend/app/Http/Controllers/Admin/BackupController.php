<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class BackupController extends Controller
{
    /**
     * Tampilkan halaman Manajemen Cadangan & Pemulihan (Backup & Restore).
     */
    public function index(Request $request)
    {
        $backups = BackupService::listBackups();
        $tables = BackupService::getDatabaseTables();

        $totalSizeBytes = array_sum(array_column($backups, 'size_bytes'));
        $zipCount = count(array_filter($backups, fn($b) => $b['format'] === 'zip'));
        $jsonCount = count(array_filter($backups, fn($b) => $b['format'] === 'json'));

        $stats = [
            'total_backups'     => count($backups),
            'total_size_human'  => BackupService::formatBytes($totalSizeBytes),
            'zip_count'         => $zipCount,
            'json_count'        => $jsonCount,
            'latest_backup'     => $backups[0] ?? null,
            'tables_count'      => count($tables),
        ];

        $settings = [
            'backup_schedule_enabled'         => filter_var(Setting::get('backup_schedule_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'backup_schedule_frequency'       => Setting::get('backup_schedule_frequency', 'daily'),
            'backup_schedule_time'            => Setting::get('backup_schedule_time', '02:00'),
            'backup_schedule_format'          => Setting::get('backup_schedule_format', 'both'),
            'backup_retention_days'           => (int) Setting::get('backup_retention_days', 14),
            'backup_last_run_at'              => Setting::get('backup_last_run_at', null),
            'backup_last_status'              => Setting::get('backup_last_status', null),
            'backup_last_message'             => Setting::get('backup_last_message', null),
            'backup_gdrive_enabled'           => filter_var(Setting::get('backup_gdrive_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'backup_gdrive_client_id'         => Setting::get('backup_gdrive_client_id', ''),
            'backup_gdrive_client_secret'     => Setting::get('backup_gdrive_client_secret', ''),
            'backup_gdrive_refresh_token'     => Setting::get('backup_gdrive_refresh_token', ''),
            'backup_gdrive_folder_id'         => Setting::get('backup_gdrive_folder_id', ''),
            'backup_gdrive_last_sync_at'      => Setting::get('backup_gdrive_last_sync_at', null),
            'backup_gdrive_last_sync_status'  => Setting::get('backup_gdrive_last_sync_status', null),
            'backup_gdrive_last_sync_message' => Setting::get('backup_gdrive_last_sync_message', null),
        ];

        return Inertia::render('Admin/Settings/Backup', [
            'backups'  => $backups,
            'tables'   => $tables,
            'stats'    => $stats,
            'settings' => $settings,
        ]);
    }

    /**
     * Buat cadangan baru (ZIP atau JSON).
     */
    public function createBackup(Request $request)
    {
        $validated = $request->validate([
            'format'      => ['required', 'in:zip,json'],
            'sync_gdrive' => ['nullable', 'boolean'],
            'note'        => ['nullable', 'string', 'max:50'],
        ]);

        $format = $validated['format'];
        $syncGdrive = $request->boolean('sync_gdrive');
        $customNote = !empty($validated['note']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $validated['note']) : null;

        if ($format === 'zip') {
            $result = BackupService::createZipBackup($customNote);
        } else {
            $result = BackupService::createJsonBackup($customNote);
        }

        if (!$result['success']) {
            return back()->with('error', $result['message'] ?? 'Gagal membuat berkas cadangan.');
        }

        $gdriveMessage = '';
        if ($syncGdrive) {
            $gdriveRes = BackupService::uploadToGoogleDrive($result['path']);
            if ($gdriveRes['success']) {
                $gdriveMessage = ' dan berhasil disinkronkan ke Google Drive';
            } else {
                $gdriveMessage = ' (Peringatan: Gagal unggah ke Google Drive: ' . ($gdriveRes['message'] ?? '') . ')';
            }
        }

        return back()->with('success', "Cadangan {$result['filename']} berhasil dibuat{$gdriveMessage}!");
    }

    /**
     * Unduh berkas cadangan.
     */
    public function downloadBackup(string $filename)
    {
        $clean = basename($filename);
        $ext = strtolower(pathinfo($clean, PATHINFO_EXTENSION));

        $path = ($ext === 'zip')
            ? BackupService::getBackupDir('zip') . '/' . $clean
            : BackupService::getBackupDir('json') . '/' . $clean;

        if (!File::exists($path)) {
            abort(404, 'Berkas cadangan tidak ditemukan di server.');
        }

        return response()->download($path, $clean);
    }

    /**
     * Hapus berkas cadangan.
     */
    public function deleteBackup(string $filename)
    {
        $clean = basename($filename);
        $deleted = BackupService::deleteBackup($clean);

        if ($deleted) {
            ActivityLog::record(
                'backup_delete',
                'Hapus Berkas Cadangan',
                "Berkas cadangan {$clean} berhasil dihapus dari server oleh " . (auth()->user()?->name ?? 'Panitia') . ".",
                [
                    'highlight'   => $clean,
                    'badge_icon'  => 'ri-delete-bin-line',
                    'badge_class' => 'bg-danger-subtle text-danger',
                ]
            );

            return back()->with('success', "Berkas cadangan {$clean} berhasil dihapus.");
        }

        return back()->with('error', 'Gagal menghapus berkas cadangan atau berkas tidak ditemukan.');
    }

    /**
     * Pulihkan aplikasi & database dari berkas unggahan atau cadangan lokal.
     */
    public function restoreBackup(Request $request)
    {
        // 1. Restore dari berkas yang diunggah
        if ($request->hasFile('backup_file')) {
            $file = $request->file('backup_file');
            $ext = strtolower($file->getClientOriginalExtension());

            if (!in_array($ext, ['zip', 'json'])) {
                return back()->with('error', 'Format berkas tidak didukung. Harap pilih berkas berekstensi .zip atau .json.');
            }

            $tempPath = $file->getRealPath();

            if ($ext === 'zip') {
                $result = BackupService::restoreZipBackup($tempPath);
            } else {
                $result = BackupService::restoreJsonBackup($tempPath);
            }

            if ($result['success']) {
                return back()->with('success', $result['message']);
            }

            return back()->with('error', $result['message'] ?? 'Gagal memulihkan cadangan.');
        }

        // 2. Restore dari berkas lokal yang sudah ada
        $filename = $request->input('filename');
        if (!empty($filename)) {
            $clean = basename($filename);
            $ext = strtolower(pathinfo($clean, PATHINFO_EXTENSION));

            $path = ($ext === 'zip')
                ? BackupService::getBackupDir('zip') . '/' . $clean
                : BackupService::getBackupDir('json') . '/' . $clean;

            if (!File::exists($path)) {
                return back()->with('error', 'Berkas cadangan lokal tidak ditemukan.');
            }

            if ($ext === 'zip') {
                $result = BackupService::restoreZipBackup($path);
            } else {
                $result = BackupService::restoreJsonBackup($path);
            }

            if ($result['success']) {
                return back()->with('success', $result['message']);
            }

            return back()->with('error', $result['message'] ?? 'Gagal memulihkan cadangan.');
        }

        return back()->with('error', 'Harap unggah berkas cadangan atau pilih cadangan yang tersedia.');
    }

    /**
     * Simpan pengaturan jadwal cadangan dan Google Drive.
     */
    public function updateScheduleSettings(Request $request)
    {
        $validated = $request->validate([
            'backup_schedule_enabled'     => ['required', 'boolean'],
            'backup_schedule_frequency'   => ['required', 'in:daily,weekly,monthly'],
            'backup_schedule_time'        => ['required', 'string', 'max:10'],
            'backup_schedule_format'      => ['required', 'in:zip,json,both'],
            'backup_retention_days'       => ['required', 'integer', 'min:1', 'max:365'],
            'backup_gdrive_enabled'       => ['required', 'boolean'],
            'backup_gdrive_client_id'     => ['nullable', 'string', 'max:255'],
            'backup_gdrive_client_secret' => ['nullable', 'string', 'max:255'],
            'backup_gdrive_refresh_token' => ['nullable', 'string', 'max:500'],
            'backup_gdrive_folder_id'     => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated as $key => $val) {
            Setting::set($key, is_bool($val) ? ($val ? '1' : '0') : (string)$val);
        }

        ActivityLog::record(
            'settings_update',
            'Pembaruan Jadwal Cadangan & Cloud',
            'Pengaturan backup otomatis terjadwal dan konfigurasi Google Drive berhasil diperbarui.',
            [
                'badge_icon'  => 'ri-settings-4-line',
                'badge_class' => 'bg-secondary-subtle text-secondary',
            ]
        );

        return back()->with('success', 'Konfigurasi jadwal cadangan dan Google Drive berhasil disimpan!');
    }

    /**
     * Uji koneksi ke Google Drive.
     */
    public function testGoogleDriveConnection(Request $request)
    {
        $result = BackupService::testGoogleDriveConnection($request->all());
        return response()->json($result);
    }

    /**
     * Sinkronkan cadangan lokal yang dipilih ke Google Drive.
     */
    public function syncToGoogleDrive(Request $request, string $filename)
    {
        $clean = basename($filename);
        $ext = strtolower(pathinfo($clean, PATHINFO_EXTENSION));

        $path = ($ext === 'zip')
            ? BackupService::getBackupDir('zip') . '/' . $clean
            : BackupService::getBackupDir('json') . '/' . $clean;

        if (!File::exists($path)) {
            return back()->with('error', 'Berkas cadangan tidak ditemukan.');
        }

        $res = BackupService::uploadToGoogleDrive($path);
        if ($res['success']) {
            return back()->with('success', "Berkas {$clean} berhasil diunggah ke Google Drive!");
        }

        return back()->with('error', "Gagal unggah ke Google Drive: " . ($res['message'] ?? ''));
    }
}
