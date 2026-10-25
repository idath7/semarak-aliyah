<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SipresensiService
{
    /**
     * Dapatkan path direktori instalasi aplikasi Sipresensi lokal
     */
    public static function getSipresensiPath(): string
    {
        $path = Setting::get('sipresensi_path', config('services.sipresensi.path') ?: env('SIPRESENSI_PATH', 'D:\\1Laravel\\sipresensi'));
        return trim((string) $path, " \t\n\r\0\x0B'\"");
    }

    /**
     * Dapatkan Base URL API Sipresensi
     */
    public static function getApiUrl(): string
    {
        $url = Setting::get('sipresensi_api_url', config('services.sipresensi.url') ?: env('SIPRESENSI_API_URL', 'http://localhost:8000'));
        return rtrim(trim((string) $url, " \t\n\r\0\x0B'\""), '/');
    }

    /**
     * Dapatkan Token API Sipresensi
     */
    public static function getApiToken(): string
    {
        $token = Setting::get('sipresensi_api_token', config('services.sipresensi.token') ?: env('SIPRESENSI_API_TOKEN', 'secret_token_123'));
        return trim((string) $token, " \t\n\r\0\x0B'\"");
    }

    /**
     * Dapatkan Mode Integrasi Sipresensi ('auto', 'http_api', 'local_artisan')
     */
    public static function getMode(): string
    {
        return Setting::get('sipresensi_mode', 'auto');
    }

    /**
     * Dapatkan Endpoint Data Guru
     */
    public static function getTeachersEndpoint(): string
    {
        $ep = Setting::get('sipresensi_teachers_endpoint', '/api/v1/integration/pkl/teachers/all');
        return '/' . ltrim((string) $ep, '/');
    }

    /**
     * Dapatkan Endpoint Verifikasi Kredensial SSO
     */
    public static function getVerifyEndpoint(): string
    {
        $ep = Setting::get('sipresensi_verify_endpoint', '/api/v1/integration/pkl/verify-credentials');
        return '/' . ltrim((string) $ep, '/');
    }

    /**
     * Dapatkan Timeout Request (Detik)
     */
    public static function getTimeout(): int
    {
        return max(2, (int) Setting::get('sipresensi_timeout', 5));
    }

    /**
     * Dapatkan konfigurasi env terisolasi milik Sipresensi langsung dari file .env aplikasinya
     * Menjamin isolasi 100% dan mencegah kebocoran env database Semarak (PostgreSQL) ke Sipresensi (MySQL)
     */
    public static function getSipresensiEnv(): array
    {
        $sipPath = self::getSipresensiPath();
        $envFile = $sipPath . DIRECTORY_SEPARATOR . '.env';
        $env = [];

        // 1. Sertakan variabel sistem Windows esensial agar socket networking (Winsock) berfungsi
        $systemVars = ['SystemRoot', 'WINDIR', 'PATH', 'TEMP', 'TMP', 'SYSTEMDRIVE', 'USERPROFILE', 'APPDATA', 'LOCALAPPDATA'];
        foreach ($systemVars as $var) {
            $val = getenv($var) ?: ($_SERVER[$var] ?? null);
            if ($val !== null) {
                $env[$var] = $val;
            }
        }

        // 2. Baca seluruh variabel .env milik Sipresensi untuk menimpa env parent Semarak
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$k, $v] = explode('=', $line, 2);
                    $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
                }
            }
        }

        return $env;
    }

    /**
     * Ambil seluruh data guru dari Sipresensi tanpa direct database connection
     * (Prioritas 1: REST API, Prioritas 2: Artisan Runner di direktori Sipresensi)
     */
    public static function fetchTeachers(): array
    {
        $mode = self::getMode();
        $apiUrl = self::getApiUrl();
        $token = self::getApiToken();
        $teachersEp = self::getTeachersEndpoint();
        $timeout = self::getTimeout();

        // 1. Coba melalui REST API HTTP Endpoint (kecuali mode hanya local_artisan)
        if ($mode !== 'local_artisan' && !empty($apiUrl) && !str_contains($apiUrl, ':8001')) {
            try {
                $response = Http::timeout($timeout)
                    ->acceptJson()
                    ->withoutVerifying()
                    ->withToken($token)
                    ->get("{$apiUrl}{$teachersEp}");

                if ($response->successful() && is_array($response->json('data'))) {
                    return [
                        'success' => true,
                        'source'  => 'http_api',
                        'data'    => $response->json('data'),
                    ];
                }
            } catch (\Throwable $e) {
                Log::info("Sipresensi HTTP API tidak dapat dijangkau ({$e->getMessage()}), beralih ke local Artisan service.");
            }
        }

        // Jika mode dipaksa hanya HTTP API dan gagal
        if ($mode === 'http_api') {
            return [
                'success' => false,
                'source'  => 'http_api',
                'message' => 'Layanan HTTP API Sipresensi tidak dapat dijangkau dan mode diatur hanya REST API.',
                'data'    => [],
            ];
        }

        // 2. Fallback: Eksekusi Artisan Command resmi di dalam Sipresensi secara terisolasi tanpa direct DB coupling
        $sipPath = self::getSipresensiPath();
        $artisanPath = $sipPath . DIRECTORY_SEPARATOR . 'artisan';

        if (file_exists($artisanPath)) {
            try {
                $phpBinary = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
                $process = new Process([$phpBinary, 'artisan', 'sipresensi:export-teachers'], $sipPath, self::getSipresensiEnv());
                $process->setTimeout(15);
                $process->run();

                if ($process->isSuccessful()) {
                    $output = trim($process->getOutput());
                    $decoded = json_decode($output, true);
                    if (is_array($decoded) && !empty($decoded['success']) && isset($decoded['data'])) {
                        return [
                            'success' => true,
                            'source'  => 'artisan_cli',
                            'data'    => $decoded['data'],
                        ];
                    }
                }
                Log::warning('Artisan export-teachers Sipresensi gagal. Exit: ' . $process->getExitCode() . ' Output: ' . $process->getOutput() . ' Stderr: ' . $process->getErrorOutput());
            } catch (\Throwable $e) {
                Log::error('Eksekusi Artisan Sipresensi gagal: ' . $e->getMessage());
            }
        } else {
            Log::warning("Artisan Sipresensi tidak ditemukan di path: {$artisanPath}");
        }

        return [
            'success' => false,
            'source'  => 'none',
            'message' => 'Layanan Sipresensi tidak dapat dihubungi via HTTP API maupun Artisan Service lokal.',
            'data'    => [],
        ];
    }

    /**
     * Verifikasi kredensial login akun Sipresensi (SSO)
     * (Prioritas 1: REST API Endpoint, Prioritas 2: Artisan Runner di direktori Sipresensi)
     */
    public static function verifyCredentials(string $identifier, string $password): array
    {
        $identifier = trim($identifier);
        if (empty($identifier) || empty($password)) {
            return [
                'success' => false,
                'message' => 'NIP / Identifier dan kata sandi wajib diisi.',
            ];
        }

        $mode = self::getMode();
        $apiUrl = self::getApiUrl();
        $token = self::getApiToken();
        $verifyEp = self::getVerifyEndpoint();
        $timeout = self::getTimeout();

        // 1. Coba verifikasi via REST API HTTP Endpoint (kecuali mode hanya local_artisan)
        if ($mode !== 'local_artisan' && !empty($apiUrl)) {
            try {
                $response = Http::timeout($timeout)
                    ->acceptJson()
                    ->withoutVerifying()
                    ->withToken($token)
                    ->post("{$apiUrl}{$verifyEp}", [
                        'identifier' => $identifier,
                        'password'   => $password,
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    if (!empty($json['success']) && isset($json['data'])) {
                        return [
                            'success' => true,
                            'source'  => 'http_api',
                            'message' => $json['message'] ?? 'Autentikasi Sipresensi berhasil.',
                            'data'    => $json['data'],
                        ];
                    }
                } elseif ($response->status() === 401 || $response->status() === 422) {
                    return [
                        'success' => false,
                        'message' => $response->json('message') ?: 'Kredensial akun Sipresensi tidak sesuai.',
                    ];
                }
            } catch (\Throwable $e) {
                Log::info("Sipresensi verify API offline ({$e->getMessage()}), mencoba local Artisan service.");
            }
        }

        if ($mode === 'http_api') {
            return [
                'success' => false,
                'message' => 'Server API Sipresensi tidak dapat dihubungi dan mode integrasi dikunci ke REST API Only.',
            ];
        }

        // 2. Fallback: Eksekusi Artisan Command resmi di dalam Sipresensi secara terisolasi tanpa direct DB coupling
        $sipPath = self::getSipresensiPath();
        $artisanPath = $sipPath . DIRECTORY_SEPARATOR . 'artisan';

        if (file_exists($artisanPath)) {
            try {
                $phpBinary = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
                $process = new Process([
                    $phpBinary, 'artisan', 'sipresensi:export-teachers',
                    "--verify-user={$identifier}",
                    "--verify-password={$password}"
                ], $sipPath, self::getSipresensiEnv());

                $process->setTimeout(15);
                $process->run();

                $output = trim($process->getOutput());
                $decoded = json_decode($output, true);
                if (is_array($decoded) && isset($decoded['success'])) {
                    return $decoded;
                }
            } catch (\Throwable $e) {
                Log::error('Eksekusi verify Artisan Sipresensi gagal: ' . $e->getMessage());
            }
        }

        return [
            'success' => false,
            'message' => 'Tidak dapat menghubungi layanan autentikasi Sipresensi.',
        ];
    }

    /**
     * Uji Diagnostik Koneksi & Integritas Sipresensi (HTTP API & Local Runner)
     */
    public static function testConnection(): array
    {
        $mode = self::getMode();
        $apiUrl = self::getApiUrl();
        $token = self::getApiToken();
        $timeout = self::getTimeout();
        $teachersEp = self::getTeachersEndpoint();
        $sipPath = self::getSipresensiPath();

        $report = [
            'mode' => $mode,
            'http_api' => [
                'url' => "{$apiUrl}{$teachersEp}",
                'status' => 'untested',
                'reachable' => false,
                'latency_ms' => null,
                'status_code' => null,
                'message' => '',
            ],
            'local_artisan' => [
                'path' => $sipPath,
                'path_exists' => false,
                'artisan_exists' => false,
                'env_exists' => false,
                'app_name' => null,
                'db_connection' => null,
                'status' => 'untested',
                'message' => '',
            ],
            'overall_status' => 'disconnected',
            'recommended_action' => '',
        ];

        // 1. Uji HTTP API Endpoint
        if ($mode !== 'local_artisan') {
            $startTime = microtime(true);
            try {
                $response = Http::timeout($timeout)
                    ->acceptJson()
                    ->withoutVerifying()
                    ->withToken($token)
                    ->get("{$apiUrl}{$teachersEp}");

                $latency = (int) round((microtime(true) - $startTime) * 1000);
                $report['http_api']['latency_ms'] = $latency;
                $report['http_api']['status_code'] = $response->status();

                if ($response->successful()) {
                    $data = $response->json('data');
                    $count = is_array($data) ? count($data) : 0;
                    $report['http_api']['reachable'] = true;
                    $report['http_api']['status'] = 'online';
                    $report['http_api']['message'] = "API berhasil merespons ({$latency} ms). Terbaca {$count} data guru.";
                    $report['overall_status'] = 'connected_api';
                } elseif ($response->status() === 401 || $response->status() === 403) {
                    $report['http_api']['status'] = 'unauthorized';
                    $report['http_api']['message'] = "API merespons status HTTP {$response->status()} (Token API salah atau tidak diizinkan).";
                } else {
                    $report['http_api']['status'] = 'error';
                    $report['http_api']['message'] = "API merespons status HTTP {$response->status()}.";
                }
            } catch (\Throwable $e) {
                $report['http_api']['status'] = 'offline';
                $report['http_api']['message'] = "Gagal terhubung ke HTTP API: " . $e->getMessage();
            }
        } else {
            $report['http_api']['status'] = 'disabled';
            $report['http_api']['message'] = 'Pengujian HTTP API dilewati karena mode integrasi diatur ke "Hanya Local Artisan CLI".';
        }

        // 2. Uji Local Artisan Path
        if (file_exists($sipPath)) {
            $report['local_artisan']['path_exists'] = true;
            $artisanFile = $sipPath . DIRECTORY_SEPARATOR . 'artisan';
            $envFile = $sipPath . DIRECTORY_SEPARATOR . '.env';

            if (file_exists($artisanFile)) {
                $report['local_artisan']['artisan_exists'] = true;
            }
            if (file_exists($envFile)) {
                $report['local_artisan']['env_exists'] = true;
                $env = self::getSipresensiEnv();
                $report['local_artisan']['app_name'] = $env['APP_NAME'] ?? 'Sipresensi';
                $report['local_artisan']['db_connection'] = ($env['DB_CONNECTION'] ?? 'mysql') . ' (' . ($env['DB_DATABASE'] ?? 'sipresensi') . ')';
            }

            if ($report['local_artisan']['artisan_exists']) {
                $report['local_artisan']['status'] = 'ready';
                $report['local_artisan']['message'] = 'Direktori & file artisan Sipresensi valid. Siap mengeksekusi integrasi lokal.';
                if ($report['overall_status'] !== 'connected_api') {
                    $report['overall_status'] = 'connected_artisan';
                }
            } else {
                $report['local_artisan']['status'] = 'incomplete';
                $report['local_artisan']['message'] = 'Folder ditemukan namun file artisan tidak ada di dalam folder tersebut.';
            }
        } else {
            $report['local_artisan']['status'] = 'not_found';
            $report['local_artisan']['message'] = "Folder {$sipPath} tidak ditemukan di komputer server.";
        }

        if ($report['overall_status'] === 'connected_api') {
            $report['recommended_action'] = 'Layanan REST API Sipresensi berjalan lancar dan siap digunakan untuk SSO maupun sinkronisasi data guru.';
        } elseif ($report['overall_status'] === 'connected_artisan') {
            $report['recommended_action'] = 'Jalur eksekusi Artisan CLI lokal siap digunakan secara terisolasi tanpa coupling langsung ke database.';
        } else {
            $report['recommended_action'] = 'Periksa apakah URL API Sipresensi sedang aktif atau sesuaikan path folder instalasi Sipresensi lokal.';
        }

        return $report;
    }
}

