<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupService
{
    /**
     * Internal tables excluded from user-data backups.
     */
    protected static array $excludedTables = [
        'migrations',
        'jobs',
        'failed_jobs',
        'job_batches',
        'cache',
        'cache_locks',
        'sessions',
        'personal_access_tokens',
    ];

    /**
     * Get root backup directory.
     */
    public static function getBackupDir(string $subDir = ''): string
    {
        $base = storage_path('app/backups');
        $path = $subDir ? "{$base}/{$subDir}" : $base;
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true, true);
        }
        return $path;
    }

    /**
     * Get all tables in current database schema.
     */
    public static function getDatabaseTables(): array
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver", 'pgsql');

        try {
            if ($driver === 'pgsql') {
                $tables = DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
                $names = array_map(fn($t) => $t->tablename, $tables);
            } elseif ($driver === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $names = array_map(fn($t) => $t->name, $tables);
            } else {
                $tables = DB::select('SHOW TABLES');
                $names = array_map(fn($t) => array_values((array)$t)[0], $tables);
            }

            // Exclude temporary/cache/job tables
            return array_values(array_filter($names, function ($name) {
                return !in_array($name, static::$excludedTables);
            }));
        } catch (\Throwable $e) {
            Log::warning('BackupService getDatabaseTables fallback: ' . $e->getMessage());
            // Fallback list of known application tables
            return [
                'users',
                'categories',
                'competitions',
                'registrations',
                'schools',
                'settings',
                'sponsors',
                'galleries',
                'faqs',
                'wa_logs',
                'activity_logs',
                'roles',
                'permissions',
                'permission_role',
                'tournament_matches',
                'jury_scores',
                'registrants',
            ];
        }
    }

    /**
     * Dump all database tables into an associative array.
     */
    public static function dumpDatabaseData(): array
    {
        $tables = static::getDatabaseTables();
        $data = [];
        $totalRecords = 0;

        foreach ($tables as $table) {
            try {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $rows = DB::table($table)->orderBy('id', 'asc')->get();
                    $arrayRows = json_decode(json_encode($rows), true);
                    $data[$table] = $arrayRows;
                    $totalRecords += count($arrayRows);
                }
            } catch (\Throwable $e) {
                Log::warning("BackupService dump table [{$table}] error: " . $e->getMessage());
            }
        }

        return [
            'tables'        => $data,
            'total_records' => $totalRecords,
            'table_names'   => array_keys($data),
        ];
    }

    /**
     * 1. CREATE FULL ZIP BACKUP (Application Storage & Complete Database)
     */
    public static function createZipBackup(?string $customName = null): array
    {
        if (!class_exists('ZipArchive')) {
            return [
                'success' => false,
                'message' => 'Ekstensi PHP ZipArchive tidak aktif pada server ini.',
            ];
        }

        $zipDir = static::getBackupDir('zip');
        $timestamp = now()->format('Y-m-d_His');
        $dateFormatted = now()->format('d/m/Y H:i:s');
        $fileName = $customName ? "semarak_backup_full_{$customName}_{$timestamp}.zip" : "semarak_backup_full_{$timestamp}.zip";
        $filePath = "{$zipDir}/{$fileName}";

        $dbDump = static::dumpDatabaseData();
        $allSettings = Setting::getAll();

        $manifest = [
            'backup_format'   => 'semarak_full_backup_zip',
            'version'         => '1.0',
            'generated_at'    => now()->toIso8601String(),
            'formatted_date'  => $dateFormatted,
            'app_name'        => Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026'),
            'database_driver' => config('database.default'),
            'tables_count'    => count($dbDump['table_names']),
            'total_records'   => $dbDump['total_records'],
            'table_list'      => $dbDump['table_names'],
            'php_version'     => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return [
                'success' => false,
                'message' => 'Gagal membuka berkas ZIP untuk penulisan cadangan.',
            ];
        }

        // 1. Simpan metadata manifest.json
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // 2. Simpan database snapshot dalam format JSON
        $zip->addFromString('database.json', json_encode($dbDump['tables'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // 3. Simpan setting sistem
        $zip->addFromString('settings.json', json_encode($allSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // 4. Buat file SQL insert dump
        $sqlDump = static::generateSqlDump($dbDump['tables']);
        $zip->addFromString('database.sql', $sqlDump);

        // 5. Arsipkan seluruh file uploads pada storage/app/public
        $publicStoragePath = storage_path('app/public');
        $fileCount = 0;
        if (File::isDirectory($publicStoragePath)) {
            $files = File::allFiles($publicStoragePath);
            foreach ($files as $file) {
                $relative = 'storage_public/' . str_replace('\\', '/', $file->getRelativePathname());
                $zip->addFile($file->getRealPath(), $relative);
                $fileCount++;
            }
        }

        $zip->close();

        $fileSize = File::size($filePath);
        static::purgeOldBackups('zip');

        ActivityLog::record(
            'backup',
            'Cadangan Aplikasi & Database (ZIP)',
            "Pembuatan berkas cadangan ZIP lengkap berhasil: {$fileName} (" . static::formatBytes($fileSize) . ", {$dbDump['total_records']} baris data, {$fileCount} berkas unggahan).",
            [
                'highlight'  => $fileName,
                'badge_icon' => 'ri-file-zip-line',
                'badge_class'=> 'bg-warning-subtle text-warning',
                'properties' => [
                    'filename'   => $fileName,
                    'size_bytes' => $fileSize,
                    'format'     => 'zip',
                    'records'    => $dbDump['total_records'],
                    'files'      => $fileCount,
                ],
            ]
        );

        return [
            'success'       => true,
            'filename'      => $fileName,
            'path'          => $filePath,
            'format'        => 'zip',
            'size_bytes'    => $fileSize,
            'size_human'    => static::formatBytes($fileSize),
            'records_count' => $dbDump['total_records'],
            'files_count'   => $fileCount,
            'message'       => "Cadangan ZIP berhasil dibuat ({$fileName}).",
        ];
    }

    /**
     * 2. CREATE PORTABLE JSON BACKUP (Complete Database & Settings Snapshot)
     */
    public static function createJsonBackup(?string $customName = null): array
    {
        $jsonDir = static::getBackupDir('json');
        $timestamp = now()->format('Y-m-d_His');
        $dateFormatted = now()->format('d/m/Y H:i:s');
        $fileName = $customName ? "semarak_backup_data_{$customName}_{$timestamp}.json" : "semarak_backup_data_{$timestamp}.json";
        $filePath = "{$jsonDir}/{$fileName}";

        $dbDump = static::dumpDatabaseData();
        $allSettings = Setting::getAll();

        $payload = [
            'backup_format'   => 'semarak_full_backup_json',
            'version'         => '1.0',
            'generated_at'    => now()->toIso8601String(),
            'formatted_date'  => $dateFormatted,
            'app_name'        => Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026'),
            'database_driver' => config('database.default'),
            'tables_count'    => count($dbDump['table_names']),
            'total_records'   => $dbDump['total_records'],
            'settings_count'  => count($allSettings),
            'tables'          => $dbDump['tables'],
            'settings'        => $allSettings,
            'metadata'        => [
                'user'            => auth()->user()?->name ?? 'Sistem',
                'php_version'     => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
        ];

        File::put($filePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $fileSize = File::size($filePath);
        static::purgeOldBackups('json');

        ActivityLog::record(
            'backup',
            'Cadangan Data & Pengaturan (JSON)',
            "Pembuatan berkas cadangan JSON berhasil: {$fileName} (" . static::formatBytes($fileSize) . ", {$dbDump['total_records']} baris data dari " . count($dbDump['table_names']) . " tabel).",
            [
                'highlight'  => $fileName,
                'badge_icon' => 'ri-braces-line',
                'badge_class'=> 'bg-info-subtle text-info',
                'properties' => [
                    'filename'   => $fileName,
                    'size_bytes' => $fileSize,
                    'format'     => 'json',
                    'records'    => $dbDump['total_records'],
                ],
            ]
        );

        return [
            'success'       => true,
            'filename'      => $fileName,
            'path'          => $filePath,
            'format'        => 'json',
            'size_bytes'    => $fileSize,
            'size_human'    => static::formatBytes($fileSize),
            'records_count' => $dbDump['total_records'],
            'message'       => "Cadangan JSON berhasil dibuat ({$fileName}).",
        ];
    }

    /**
     * 3. RESTORE FROM ZIP BACKUP
     */
    public static function restoreZipBackup(string $zipPath): array
    {
        if (!File::exists($zipPath)) {
            return ['success' => false, 'message' => 'Berkas cadangan ZIP tidak ditemukan di server.'];
        }

        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'Ekstensi PHP ZipArchive tidak aktif.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'message' => 'Format berkas ZIP tidak sah atau rusak.'];
        }

        $tempDir = storage_path('app/backups/tmp_restore_' . uniqid());
        File::makeDirectory($tempDir, 0755, true, true);
        $zip->extractTo($tempDir);
        $zip->close();

        try {
            $dbJsonFile = "{$tempDir}/database.json";
            if (!File::exists($dbJsonFile)) {
                File::deleteDirectory($tempDir);
                return ['success' => false, 'message' => 'Berkas ZIP tidak memuat snapshot database.json yang valid.'];
            }

            $tables = json_decode(File::get($dbJsonFile), true);
            if (!is_array($tables)) {
                File::deleteDirectory($tempDir);
                return ['success' => false, 'message' => 'Data struktur snapshot database.json rusak.'];
            }

            // Restore database records
            $restoredCount = static::restoreTablesData($tables);

            // Restore settings if present
            $settingsFile = "{$tempDir}/settings.json";
            if (File::exists($settingsFile)) {
                $settings = json_decode(File::get($settingsFile), true);
                if (is_array($settings)) {
                    foreach ($settings as $k => $v) {
                        Setting::set($k, $v);
                    }
                }
            }

            // Restore public uploads/storage
            $extractedStorage = "{$tempDir}/storage_public";
            $restoredFiles = 0;
            if (File::isDirectory($extractedStorage)) {
                $targetStorage = storage_path('app/public');
                File::ensureDirectoryExists($targetStorage);
                File::copyDirectory($extractedStorage, $targetStorage);
                $restoredFiles = count(File::allFiles($extractedStorage));
            }

            // Clean up temporary files
            File::deleteDirectory($tempDir);

            // Clear cache
            try {
                \Illuminate\Support\Facades\Artisan::call('cache:clear');
            } catch (\Throwable $e) {}

            $fileName = basename($zipPath);
            ActivityLog::record(
                'restore',
                'Pemulihan Aplikasi & Database (ZIP)',
                "Pemulihan sistem berhasil dari berkas ZIP {$fileName} ({$restoredCount} baris database dan {$restoredFiles} berkas media dipulihkan).",
                [
                    'highlight'  => $fileName,
                    'badge_icon' => 'ri-restart-line',
                    'badge_class'=> 'bg-success-subtle text-success',
                    'properties' => [
                        'filename'       => $fileName,
                        'restored_rows'  => $restoredCount,
                        'restored_files' => $restoredFiles,
                    ],
                ]
            );

            return [
                'success'        => true,
                'restored_rows'  => $restoredCount,
                'restored_files' => $restoredFiles,
                'message'        => "Pemulihan aplikasi & database berhasil ({$restoredCount} baris data dipulihkan).",
            ];
        } catch (\Throwable $e) {
            File::deleteDirectory($tempDir);
            Log::error('Restore ZIP error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat memulihkan berkas ZIP: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 4. RESTORE FROM JSON BACKUP
     */
    public static function restoreJsonBackup(string $jsonPath): array
    {
        if (!File::exists($jsonPath)) {
            return ['success' => false, 'message' => 'Berkas cadangan JSON tidak ditemukan.'];
        }

        try {
            $raw = File::get($jsonPath);
            $data = json_decode($raw, true);

            if (!is_array($data) || empty($data['tables'])) {
                return ['success' => false, 'message' => 'Format berkas JSON tidak sesuai dengan spesifikasi cadangan Semarak.'];
            }

            $tables = $data['tables'];
            $restoredCount = static::restoreTablesData($tables);

            // Restore settings if available
            if (!empty($data['settings']) && is_array($data['settings'])) {
                foreach ($data['settings'] as $k => $v) {
                    Setting::set($k, $v);
                }
            }

            try {
                \Illuminate\Support\Facades\Artisan::call('cache:clear');
            } catch (\Throwable $e) {}

            $fileName = basename($jsonPath);
            ActivityLog::record(
                'restore',
                'Pemulihan Data & Pengaturan (JSON)',
                "Pemulihan database & pengaturan berhasil dari berkas JSON {$fileName} ({$restoredCount} baris data dipulihkan).",
                [
                    'highlight'  => $fileName,
                    'badge_icon' => 'ri-restart-line',
                    'badge_class'=> 'bg-success-subtle text-success',
                    'properties' => [
                        'filename'      => $fileName,
                        'restored_rows' => $restoredCount,
                    ],
                ]
            );

            return [
                'success'       => true,
                'restored_rows' => $restoredCount,
                'message'       => "Pemulihan data JSON berhasil ({$restoredCount} baris data dipulihkan).",
            ];
        } catch (\Throwable $e) {
            Log::error('Restore JSON error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal memulihkan cadangan JSON: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Atomically restore tables data inside database transaction.
     */
    protected static function restoreTablesData(array $tables): int
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver", 'pgsql');
        $totalRestored = 0;

        DB::beginTransaction();

        try {
            // Disable foreign key constraints temporarily
            if ($driver === 'pgsql') {
                DB::statement("SET session_replication_role = 'replica';");
            } elseif ($driver === 'sqlite') {
                DB::statement("PRAGMA foreign_keys = OFF;");
            } else {
                DB::statement("SET FOREIGN_KEY_CHECKS=0;");
            }

            foreach ($tables as $tableName => $rows) {
                if (!DB::getSchemaBuilder()->hasTable($tableName)) {
                    continue;
                }

                // Delete current rows
                DB::table($tableName)->delete();

                if (!empty($rows) && is_array($rows)) {
                    // Normalize JSON cast attributes for PostgreSQL / SQLite
                    $chunks = array_chunk($rows, 200);
                    foreach ($chunks as $chunk) {
                        $preparedChunk = [];
                        foreach ($chunk as $row) {
                            $preparedRow = [];
                            foreach ($row as $col => $val) {
                                if (is_array($val)) {
                                    $preparedRow[$col] = json_encode($val);
                                } elseif (is_bool($val)) {
                                    $preparedRow[$col] = $val ? 1 : 0;
                                } else {
                                    $preparedRow[$col] = $val;
                                }
                            }
                            $preparedChunk[] = $preparedRow;
                        }
                        DB::table($tableName)->insert($preparedChunk);
                        $totalRestored += count($preparedChunk);
                    }

                    // Reset auto-increment sequence for PostgreSQL
                    if ($driver === 'pgsql') {
                        try {
                            $maxId = DB::table($tableName)->max('id');
                            if ($maxId) {
                                DB::select("SELECT setval(pg_get_serial_sequence('{$tableName}', 'id'), coalesce(max(id), 1), max(id) IS NOT null) FROM \"{$tableName}\";");
                            }
                        } catch (\Throwable $e) {}
                    }
                }
            }

            // Re-enable foreign key constraints
            if ($driver === 'pgsql') {
                DB::statement("SET session_replication_role = 'origin';");
            } elseif ($driver === 'sqlite') {
                DB::statement("PRAGMA foreign_keys = ON;");
            } else {
                DB::statement("SET FOREIGN_KEY_CHECKS=1;");
            }

            DB::commit();
            return $totalRestored;
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($driver === 'pgsql') {
                DB::statement("SET session_replication_role = 'origin';");
            } elseif ($driver === 'sqlite') {
                DB::statement("PRAGMA foreign_keys = ON;");
            } else {
                DB::statement("SET FOREIGN_KEY_CHECKS=1;");
            }
            throw $e;
        }
    }

    /**
     * Generate SQL INSERT statements.
     */
    protected static function generateSqlDump(array $tables): string
    {
        $sql = "-- =====================================================================\n";
        $sql .= "-- SEMARAK ALIYAH XIV 2026 - DATABASE SQL BACKUP\n";
        $sql .= "-- Generated: " . now()->toIso8601String() . "\n";
        $sql .= "-- =====================================================================\n\n";

        foreach ($tables as $table => $rows) {
            if (empty($rows)) continue;
            $sql .= "-- Table: {$table}\n";
            foreach ($rows as $row) {
                $cols = array_keys($row);
                $escapedCols = array_map(fn($c) => "\"{$c}\"", $cols);
                $escapedVals = array_map(function($v) {
                    if (is_null($v)) return 'NULL';
                    if (is_bool($v)) return $v ? 'TRUE' : 'FALSE';
                    if (is_array($v)) return "'" . addslashes(json_encode($v)) . "'";
                    return "'" . addslashes((string)$v) . "'";
                }, array_values($row));

                $sql .= "INSERT INTO \"{$table}\" (" . implode(', ', $escapedCols) . ") VALUES (" . implode(', ', $escapedVals) . ");\n";
            }
            $sql .= "\n";
        }

        return $sql;
    }

    /**
     * 5. LIST ALL LOCAL BACKUPS
     */
    public static function listBackups(): array
    {
        $list = [];
        $zipDir = static::getBackupDir('zip');
        $jsonDir = static::getBackupDir('json');

        // Scan ZIP backups
        if (File::isDirectory($zipDir)) {
            foreach (File::files($zipDir) as $file) {
                if ($file->getExtension() === 'zip') {
                    $timestamp = $file->getMTime();
                    $list[] = [
                        'filename'    => $file->getFilename(),
                        'format'      => 'zip',
                        'scope'       => 'Aplikasi & Database Lengkap',
                        'size_bytes'  => $file->getSize(),
                        'size_human'  => static::formatBytes($file->getSize()),
                        'created_at'  => date('d/m/Y H:i', $timestamp),
                        'timestamp'   => $timestamp,
                        'download_url'=> route('admin.settings.backup.download', ['filename' => $file->getFilename()]),
                    ];
                }
            }
        }

        // Scan JSON backups
        if (File::isDirectory($jsonDir)) {
            foreach (File::files($jsonDir) as $file) {
                if ($file->getExtension() === 'json') {
                    $timestamp = $file->getMTime();
                    $list[] = [
                        'filename'    => $file->getFilename(),
                        'format'      => 'json',
                        'scope'       => 'Data Database & Pengaturan',
                        'size_bytes'  => $file->getSize(),
                        'size_human'  => static::formatBytes($file->getSize()),
                        'created_at'  => date('d/m/Y H:i', $timestamp),
                        'timestamp'   => $timestamp,
                        'download_url'=> route('admin.settings.backup.download', ['filename' => $file->getFilename()]),
                    ];
                }
            }
        }

        usort($list, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        return $list;
    }

    /**
     * Delete backup by filename.
     */
    public static function deleteBackup(string $filename): bool
    {
        $clean = basename($filename);
        $ext = strtolower(pathinfo($clean, PATHINFO_EXTENSION));

        $path = ($ext === 'zip')
            ? static::getBackupDir('zip') . '/' . $clean
            : static::getBackupDir('json') . '/' . $clean;

        if (File::exists($path)) {
            return File::delete($path);
        }

        return false;
    }

    /**
     * 6. GOOGLE DRIVE INTEGRATION
     */
    public static function testGoogleDriveConnection(?array $customCreds = null): array
    {
        $clientId = $customCreds['client_id'] ?? Setting::get('backup_gdrive_client_id', '');
        $clientSecret = $customCreds['client_secret'] ?? Setting::get('backup_gdrive_client_secret', '');
        $refreshToken = $customCreds['refresh_token'] ?? Setting::get('backup_gdrive_refresh_token', '');
        $folderId = $customCreds['folder_id'] ?? Setting::get('backup_gdrive_folder_id', '');

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            return [
                'success' => false,
                'message' => 'Kredensial Google Drive belum lengkap (Client ID, Secret, dan Refresh Token wajib diisi).',
            ];
        }

        try {
            // Exchange refresh token for access token
            $tokenRes = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
            ]);

            if (!$tokenRes->successful() || empty($tokenRes['access_token'])) {
                $err = $tokenRes->json('error_description') ?? $tokenRes->json('error') ?? 'Gagal otentikasi token.';
                return [
                    'success' => false,
                    'message' => "Otentikasi Google OAuth gagal: {$err}",
                ];
            }

            $accessToken = $tokenRes['access_token'];

            // Test Folder access if folder_id is given
            if (!empty($folderId)) {
                $folderRes = Http::withToken($accessToken)
                    ->get("https://www.googleapis.com/drive/v3/files/{$folderId}?fields=id,name,mimeType");

                if (!$folderRes->successful()) {
                    return [
                        'success' => false,
                        'message' => "Token valid, tetapi folder Google Drive dengan ID '{$folderId}' tidak ditemukan atau tidak memiliki hak akses.",
                    ];
                }

                $folderName = $folderRes->json('name') ?? 'Root / Folder';
                return [
                    'success' => true,
                    'message' => "Koneksi Google Drive Sukses! Folder target: \"{$folderName}\" terverifikasi.",
                ];
            }

            return [
                'success' => true,
                'message' => 'Koneksi Google Drive Sukses! Token otentikasi valid.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Kesalahan koneksi Google Drive: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Upload backup file to Google Drive.
     */
    public static function uploadToGoogleDrive(string $filePath): array
    {
        if (!File::exists($filePath)) {
            return ['success' => false, 'message' => 'Berkas backup lokal tidak ditemukan untuk diunggah.'];
        }

        $clientId = Setting::get('backup_gdrive_client_id', '');
        $clientSecret = Setting::get('backup_gdrive_client_secret', '');
        $refreshToken = Setting::get('backup_gdrive_refresh_token', '');
        $folderId = Setting::get('backup_gdrive_folder_id', '');

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            return ['success' => false, 'message' => 'Kredensial Google Drive belum dikonfigurasi.'];
        }

        try {
            // Get fresh access token
            $tokenRes = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
            ]);

            if (!$tokenRes->successful() || empty($tokenRes['access_token'])) {
                return ['success' => false, 'message' => 'Gagal memperbarui token akses Google Drive.'];
            }

            $accessToken = $tokenRes['access_token'];
            $fileName = basename($filePath);
            $mimeType = str_ends_with($fileName, '.zip') ? 'application/zip' : 'application/json';

            $metadata = ['name' => $fileName];
            if (!empty($folderId)) {
                $metadata['parents'] = [$folderId];
            }

            $boundary = '-------' . uniqid();
            $delimiter = "\r\n--" . $boundary . "\r\n";
            $closeDelim = "\r\n--" . $boundary . "--";

            $fileData = File::get($filePath);
            $multipartBody = $delimiter .
                "Content-Type: application/json; charset=UTF-8\r\n\r\n" .
                json_encode($metadata) .
                $delimiter .
                "Content-Type: {$mimeType}\r\n\r\n" .
                $fileData .
                $closeDelim;

            $uploadRes = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'multipart/related; boundary=' . $boundary,
                ])
                ->withBody($multipartBody, 'multipart/related; boundary=' . $boundary)
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

            if (!$uploadRes->successful()) {
                $errMsg = $uploadRes->json('error.message') ?? 'Gagal mengunggah berkas ke Google Drive.';
                return ['success' => false, 'message' => $errMsg];
            }

            $uploadedId = $uploadRes->json('id');
            Setting::set('backup_gdrive_last_sync_at', now()->toDateTimeString());
            Setting::set('backup_gdrive_last_sync_status', 'success');
            Setting::set('backup_gdrive_last_sync_message', "Berhasil sinkron berkas {$fileName} (ID: {$uploadedId})");

            return [
                'success' => true,
                'file_id' => $uploadedId,
                'name'    => $fileName,
                'message' => "Berkas {$fileName} berhasil disinkronkan ke Google Drive!",
            ];
        } catch (\Throwable $e) {
            Setting::set('backup_gdrive_last_sync_status', 'failed');
            Setting::set('backup_gdrive_last_sync_message', $e->getMessage());
            return ['success' => false, 'message' => 'Gagal sinkron Google Drive: ' . $e->getMessage()];
        }
    }

    /**
     * 7. RUN SCHEDULED BACKUP (CLI / Cron / Scheduler)
     */
    public static function runScheduledBackup(string $format = 'both', bool $syncGdrive = false): array
    {
        $results = [];
        $createdFiles = [];

        if ($format === 'zip' || $format === 'both') {
            $zipRes = static::createZipBackup('auto');
            $results['zip'] = $zipRes;
            if ($zipRes['success']) {
                $createdFiles[] = $zipRes['path'];
            }
        }

        if ($format === 'json' || $format === 'both') {
            $jsonRes = static::createJsonBackup('auto');
            $results['json'] = $jsonRes;
            if ($jsonRes['success']) {
                $createdFiles[] = $jsonRes['path'];
            }
        }

        $gdriveResults = [];
        if ($syncGdrive && !empty($createdFiles)) {
            foreach ($createdFiles as $file) {
                $gdriveResults[basename($file)] = static::uploadToGoogleDrive($file);
            }
        }

        Setting::set('backup_last_run_at', now()->toDateTimeString());
        Setting::set('backup_last_status', 'success');
        Setting::set('backup_last_message', 'Cadangan terjadwal berhasil dieksekusi: ' . count($createdFiles) . ' berkas dibuat.');

        return [
            'success'      => true,
            'files'        => $createdFiles,
            'results'      => $results,
            'gdrive'       => $gdriveResults,
        ];
    }

    /**
     * Purge old backup files exceeding retention period.
     */
    protected static function purgeOldBackups(string $subDir): void
    {
        $retentionDays = (int) Setting::get('backup_retention_days', 14);
        if ($retentionDays <= 0) return;

        $dir = static::getBackupDir($subDir);
        if (!File::isDirectory($dir)) return;

        $cutoff = now()->subDays($retentionDays)->timestamp;
        foreach (File::files($dir) as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getRealPath());
            }
        }
    }

    /**
     * Helper to format bytes to human readable string.
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
