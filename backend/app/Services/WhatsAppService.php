<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\WaLog;
use App\Models\Registration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WhatsAppService
{
    /**
     * Check if WhatsApp Gateway is enabled globally.
     */
    public static function isEnabled(): bool
    {
        $val = Setting::get('wa_gateway_enabled');
        if ($val !== null && $val !== '') {
            return $val === '1';
        }
        return (bool) config('whatsapp.enabled', true);
    }

    /**
     * Get all WhatsApp Gateway configuration settings.
     */
    public static function getConfig(): array
    {
        $gatewayUrl = Setting::get('wa_gateway_url');
        if (empty($gatewayUrl) || $gatewayUrl === 'http://localhost:8085') {
            $gatewayUrl = config('whatsapp.gateway_url', 'https://wacld.maswahas.sch.id');
        }

        $apiKey = Setting::get('wa_api_key');
        if (empty($apiKey)) {
            $apiKey = config('whatsapp.api_key', 'owa_k1_42615b3b5088559ee636063f25e8ca7e801057e5a43091aae5ff55eb7a59dd68');
        }

        $senderSession = Setting::get('wa_sender_session');
        if (empty($senderSession) || $senderSession === 'default') {
            $senderSession = config('whatsapp.sender_session', '0571fd01-e2d4-495e-9cf7-1c3c272b7b6b');
        }

        $enabledVal = Setting::get('wa_gateway_enabled');
        $enabled = ($enabledVal !== null && $enabledVal !== '')
            ? ($enabledVal === '1')
            : (bool) config('whatsapp.enabled', true);

        return [
            'enabled' => $enabled,
            'provider' => Setting::get('wa_gateway_provider', config('whatsapp.provider', 'openwa')),
            'gateway_url' => rtrim($gatewayUrl, '/'),
            'api_key' => $apiKey,
            'sender_session' => $senderSession,
            'endpoint_send' => Setting::get('wa_endpoint_send', config('whatsapp.endpoint_send', '/sendText')),
            'endpoint_status' => Setting::get('wa_endpoint_status', config('whatsapp.endpoint_status', '/check-auth')),
            'auth_header_type' => Setting::get('wa_auth_header_type', config('whatsapp.auth_header_type', 'api_key')),
            'chat_id_format' => Setting::get('wa_chat_id_format', config('whatsapp.chat_id_format', 'c_us')),
            'ssl_bypass' => Setting::get('wa_ssl_bypass', config('whatsapp.ssl_bypass', true) ? '1' : '0') === '1',
            'notify_new_registration' => Setting::get('wa_notify_new_registration', '1') === '1',
            'notify_admin_new_registration' => Setting::get('wa_notify_admin_new_registration', '1') === '1',
            'admin_notification_numbers' => Setting::get('wa_admin_notification_numbers', config('whatsapp.admin_notification_numbers', '083850070767')),
            'admin_group_id' => Setting::get('wa_admin_group_id', ''),
            'notify_admin_group' => Setting::get('wa_notify_admin_group', '0') === '1',
            'notify_verified_registration' => Setting::get('wa_notify_verified_registration', '1') === '1',
            'notify_verified_payment' => Setting::get('wa_notify_verified_payment', '1') === '1',
            'tpl_new_registration' => Setting::get('wa_tpl_new_registration', ''),
            'tpl_admin_notification' => Setting::get('wa_tpl_admin_notification', ''),
            'tpl_verified_registration' => Setting::get('wa_tpl_verified_registration', ''),
            'tpl_verified_payment' => Setting::get('wa_tpl_verified_payment', ''),
            'tpl_direct_registration' => Setting::get('wa_tpl_direct_registration', ''),
        ];
    }

    /**
     * Check if a given string is a WhatsApp Group ID.
     */
    public static function isGroupId(?string $target): bool
    {
        if (empty($target)) {
            return false;
        }
        $target = trim($target);
        return str_ends_with($target, '@g.us')
            || preg_match('/^120363\d{10,20}(?:@g\.us)?$/', $target)
            || preg_match('/^\d{9,16}-\d{8,14}(?:@g\.us)?$/', $target);
    }

    /**
     * Normalize Indonesian phone number to international 628xxx format or group ID.
     */
    public static function normalizePhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $raw = trim($phone);

        // If target is a WhatsApp Group ID, normalize to {id}@g.us
        if (self::isGroupId($raw)) {
            return str_ends_with($raw, '@g.us') ? $raw : ($raw . '@g.us');
        }

        // Extract phone number digits if surrounded by text
        if (preg_match('/(?:(?:\+?62)|0)?8[0-9\s-]{7,15}/', $raw, $matches)) {
            $raw = $matches[0];
        }

        $digits = preg_replace('/\D/', '', $raw);
        if (empty($digits)) {
            return '';
        }

        if (str_starts_with($digits, '620')) {
            return '62' . substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            return '62' . ltrim($digits, '0');
        }

        if (!str_starts_with($digits, '62')) {
            return '62' . $digits;
        }

        return $digits;
    }

    /**
     * Format phone number or group ID to OpenWA chatId according to format settings.
     */
    public static function formatChatId(string $phone, ?string $format = null): string
    {
        if (self::isGroupId($phone)) {
            return str_ends_with($phone, '@g.us') ? $phone : ($phone . '@g.us');
        }

        $clean = self::normalizePhone($phone);
        if (empty($clean)) {
            return '';
        }

        $format = $format ?? Setting::get('wa_chat_id_format', 'c_us');

        if ($format === 'c_us') {
            // Remove existing @c.us if any, then append
            $clean = str_replace('@c.us', '', $clean);
            return $clean . '@c.us';
        }

        return $clean;
    }

    /**
     * Build HTTP headers for OpenWA API request.
     */
    protected static function buildHeaders(array $config): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $apiKey = trim($config['api_key'] ?? '');
        if (!empty($apiKey)) {
            // Multi-header compatibility for OpenWA Cloud, NestJS, and Express
            $headers['X-API-Key'] = $apiKey;
            $headers['api_key'] = $apiKey;
            $headers['api-key'] = $apiKey;

            $headerType = $config['auth_header_type'] ?? 'api_key';
            if ($headerType === 'bearer') {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }
        }

        return $headers;
    }

    /**
     * Fetch list of sessions from OpenWA server.
     */
    public static function fetchSessions(bool $forceFresh = false): array
    {
        $config = self::getConfig();
        $baseUrl = rtrim($config['gateway_url'], '/');
        if (empty($baseUrl)) {
            return [];
        }

        $cacheKey = 'openwa_sessions_' . md5($baseUrl . ($config['api_key'] ?? ''));
        if (!$forceFresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $headers = self::buildHeaders($config);

        try {
            // Attempt 1: OpenWA Multi-Session endpoint /api/sessions
            $req = Http::withHeaders($headers)->timeout(6)->connectTimeout(3);
            if ($config['ssl_bypass'] ?? true) {
                $req = $req->withoutVerifying();
            }
            $response = $req->get($baseUrl . '/api/sessions');

            if ($response->successful() && is_array($response->json())) {
                $data = $response->json();
                $sessions = array_map(function ($s) {
                    $isReady = in_array(strtolower($s['status'] ?? ''), ['ready', 'connected', 'authenticated', 'islogged']) || ($s['engineLoaded'] ?? false);
                    return [
                        'id' => $s['id'] ?? ($s['name'] ?? ''),
                        'name' => $s['name'] ?? ($s['id'] ?? 'default'),
                        'status' => $s['status'] ?? 'unknown',
                        'phone' => $s['phone'] ?? null,
                        'pushName' => $s['pushName'] ?? null,
                        'is_ready' => $isReady,
                        'label' => ($s['name'] ?? 'session') . ($s['pushName'] ? " - {$s['pushName']}" : '') . ($s['phone'] ? " ({$s['phone']})" : '') . ($isReady ? ' [Ready 🟢]' : ' [Off 🔴]'),
                    ];
                }, $data);

                Cache::put($cacheKey, $sessions, now()->addMinutes(5));
                return $sessions;
            }

            // Attempt 2: /sessions
            $req2 = Http::withHeaders($headers)->timeout(6)->connectTimeout(3);
            if ($config['ssl_bypass'] ?? true) {
                $req2 = $req2->withoutVerifying();
            }
            $response2 = $req2->get($baseUrl . '/sessions');

            if ($response2->successful() && is_array($response2->json())) {
                $data = $response2->json();
                $sessions = array_map(function ($s) {
                    $isReady = in_array(strtolower($s['status'] ?? ''), ['ready', 'connected', 'authenticated', 'islogged']) || ($s['engineLoaded'] ?? false);
                    return [
                        'id' => $s['id'] ?? ($s['name'] ?? ''),
                        'name' => $s['name'] ?? ($s['id'] ?? 'default'),
                        'status' => $s['status'] ?? 'unknown',
                        'phone' => $s['phone'] ?? null,
                        'pushName' => $s['pushName'] ?? null,
                        'is_ready' => $isReady,
                        'label' => ($s['name'] ?? 'session') . ($s['pushName'] ? " - {$s['pushName']}" : '') . ($s['phone'] ? " ({$s['phone']})" : '') . ($isReady ? ' [Ready 🟢]' : ' [Off 🔴]'),
                    ];
                }, $data);

                Cache::put($cacheKey, $sessions, now()->addMinutes(5));
                return $sessions;
            }
        } catch (\Throwable $e) {
            Log::warning('Error fetching OpenWA sessions: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Fetch list of WhatsApp groups from OpenWA server for the active session.
     */
    public static function fetchGroups(?string $sessionInput = null, bool $forceFresh = false): array
    {
        $config = self::getConfig();
        $baseUrl = rtrim($config['gateway_url'], '/');
        if (empty($baseUrl)) {
            return [];
        }

        $sessionInput = $sessionInput ?: ($config['sender_session'] ?? 'default');
        $resolvedSession = self::resolveSessionUuid($sessionInput, $config);

        $cacheKey = 'openwa_groups_' . md5($baseUrl . ($resolvedSession ?? ''));
        if (!$forceFresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $headers = self::buildHeaders($config);

        $urlsToTry = [];
        if (!empty($resolvedSession)) {
            $urlsToTry[] = "{$baseUrl}/api/sessions/{$resolvedSession}/groups";
            $urlsToTry[] = "{$baseUrl}/sessions/{$resolvedSession}/groups";
        }
        $urlsToTry[] = "{$baseUrl}/api/groups";
        $urlsToTry[] = "{$baseUrl}/getGroups";
        $urlsToTry = array_unique($urlsToTry);

        foreach ($urlsToTry as $url) {
            try {
                $req = Http::withHeaders($headers)->timeout(6)->connectTimeout(3);
                if ($config['ssl_bypass'] ?? true) {
                    $req = $req->withoutVerifying();
                }
                $response = $req->get($url);

                if ($response->successful() && is_array($response->json())) {
                    $raw = $response->json();
                    $groups = array_map(function ($g) {
                        $id = $g['id'] ?? '';
                        $name = $g['name'] ?? ($g['subject'] ?? $id);
                        return [
                            'id' => $id,
                            'name' => $name,
                            'label' => "{$name} (" . substr($id, 0, 16) . "...)",
                            'full_label' => "{$name} ({$id})",
                        ];
                    }, $raw);

                    Cache::put($cacheKey, $groups, now()->addMinutes(5));
                    return $groups;
                }
            } catch (\Throwable $e) {
                Log::warning("Error fetching OpenWA groups from {$url}: " . $e->getMessage());
            }
        }

        return [];
    }

    /**
     * Resolve session UUID from session name, UUID, or ready session fallback.
     */
    public static function resolveSessionUuid(?string $sessionInput, array $config, bool $forceFresh = false): ?string
    {
        $input = trim($sessionInput ?? '');

        // If it's already a valid UUID format, return immediately (0 ms)!
        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $input)) {
            return $input;
        }

        $cacheKey = 'openwa_resolved_uuid_' . md5($input . ($config['gateway_url'] ?? ''));
        if (!$forceFresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Fetch available sessions from OpenWA
        $sessions = self::fetchSessions($forceFresh);
        if (empty($sessions)) {
            $fallback = config('whatsapp.sender_session', '0571fd01-e2d4-495e-9cf7-1c3c272b7b6b');
            return (!empty($input) && preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $input))
                ? $input
                : $fallback;
        }

        $resolved = null;
        // 1. Look for matching name
        if (!empty($input) && $input !== 'default') {
            foreach ($sessions as $s) {
                if (strcasecmp($s['name'], $input) === 0 || strcasecmp($s['id'], $input) === 0) {
                    $resolved = $s['id'];
                    break;
                }
            }
        }

        // 2. Look for any active/ready session
        if (!$resolved) {
            foreach ($sessions as $s) {
                if ($s['is_ready']) {
                    $resolved = $s['id'];
                    break;
                }
            }
        }

        // 3. Fallback to first session or known ready session UUID
        $final = $resolved ?: ($sessions[0]['id'] ?? config('whatsapp.sender_session', '0571fd01-e2d4-495e-9cf7-1c3c272b7b6b'));
        if (empty($final) || $final === 'default' || $final === 'awh1') {
            $final = config('whatsapp.sender_session', '0571fd01-e2d4-495e-9cf7-1c3c272b7b6b');
        }

        if ($final) {
            Cache::put($cacheKey, $final, now()->addMinutes(15));
        }

        return $final;
    }

    /**
     * Test connection to the OpenWA API server.
     */
    public static function testConnection(): array
    {
        $config = self::getConfig();
        $baseUrl = rtrim($config['gateway_url'], '/');

        if (empty($baseUrl)) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'URL Server OpenWA belum dikonfigurasi.',
                'response_time_ms' => 0,
                'details' => null,
            ];
        }

        $headers = self::buildHeaders($config);
        $startTime = microtime(true);

        try {
            // First check: OpenWA Multi-Session API /api/sessions
            $req = Http::withHeaders($headers)->timeout(10);
            if ($config['ssl_bypass'] ?? true) {
                $req = $req->withoutVerifying();
            }
            $sessionsRes = $req->get($baseUrl . '/api/sessions');

            $responseTimeMs = round((microtime(true) - $startTime) * 1000);

            if ($sessionsRes->successful() && is_array($sessionsRes->json())) {
                $sessions = $sessionsRes->json();
                $readyCount = 0;
                $activeSessionInfo = null;
                $activeUuid = null;

                foreach ($sessions as $s) {
                    $isReady = in_array(strtolower($s['status'] ?? ''), ['ready', 'connected', 'authenticated', 'islogged']) || ($s['engineLoaded'] ?? false);
                    if ($isReady) {
                        $readyCount++;
                        if (!$activeSessionInfo) {
                            $name = $s['name'] ?? $s['id'];
                            $phone = $s['phone'] ? " ({$s['phone']})" : '';
                            $pushName = !empty($s['pushName']) ? " - {$s['pushName']}" : '';
                            $activeSessionInfo = "{$name}{$pushName}{$phone}";
                            $activeUuid = $s['id'];
                        }
                    }
                }

                $msg = "Terhubung dengan sukses ke Server OpenWA ({$responseTimeMs} ms). ";
                if ($activeSessionInfo) {
                    $msg .= "Sesi Siap: {$activeSessionInfo}.";
                } elseif (count($sessions) > 0) {
                    $msg .= "Ditemukan " . count($sessions) . " sesi di server.";
                }

                return [
                    'success' => true,
                    'status' => 'connected',
                    'message' => $msg,
                    'status_code' => $sessionsRes->status(),
                    'response_time_ms' => $responseTimeMs,
                    'details' => [
                        'type' => 'openwa_multisession',
                        'total_sessions' => count($sessions),
                        'ready_sessions' => $readyCount,
                        'active_session' => $activeSessionInfo,
                        'active_session_uuid' => $activeUuid,
                    ],
                ];
            }

            // Fallback for single-session OpenWA: /check-auth or /getMe
            $endpoint = '/' . ltrim($config['endpoint_status'] ?: '/check-auth', '/');
            $fullUrl = $baseUrl . $endpoint;

            $reqSingle = Http::withHeaders($headers)->timeout(8);
            if ($config['ssl_bypass'] ?? true) {
                $reqSingle = $reqSingle->withoutVerifying();
            }
            $response = $reqSingle->get($fullUrl);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => 'connected',
                    'message' => "Terhubung dengan sukses ke Server OpenWA ({$responseTimeMs} ms)",
                    'status_code' => $response->status(),
                    'response_time_ms' => $responseTimeMs,
                    'details' => $response->json() ?? $response->body(),
                ];
            }

            return [
                'success' => false,
                'status' => 'unreachable',
                'message' => "Server OpenWA merespon dengan kode HTTP {$response->status()}",
                'status_code' => $response->status(),
                'response_time_ms' => $responseTimeMs,
                'details' => $response->json() ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            $responseTimeMs = round((microtime(true) - $startTime) * 1000);
            return [
                'success' => false,
                'status' => 'offline',
                'message' => "Gagal terhubung ke Server OpenWA: " . $e->getMessage(),
                'response_time_ms' => $responseTimeMs,
                'details' => null,
            ];
        }
    }

    /**
     * Send a single WhatsApp message via OpenWA Gateway.
     */
    public static function sendMessage(
        string $phone,
        string $message,
        string $type = 'manual',
        ?string $recipientName = null,
        bool $bypassDisabled = false
    ): array {
        $config = self::getConfig();

        if (!$bypassDisabled && !$config['enabled']) {
            return [
                'success' => false,
                'message' => 'WhatsApp Gateway sedang nonaktif di pengaturan.',
            ];
        }

        $isGroup = self::isGroupId($phone);
        $cleanPhone = $isGroup
            ? (str_ends_with($phone, '@g.us') ? $phone : $phone . '@g.us')
            : self::normalizePhone($phone);

        if (empty($cleanPhone)) {
            return [
                'success' => false,
                'message' => $isGroup ? 'ID Grup WhatsApp tujuan tidak valid.' : 'Nomor WhatsApp tujuan tidak valid.',
            ];
        }

        $chatId = self::formatChatId($cleanPhone, $config['chat_id_format']);
        $baseUrl = rtrim($config['gateway_url'], '/');
        $headers = self::buildHeaders($config);

        // Resolve Session UUID for OpenWA Cloud multi-session
        $sessionInput = trim($config['sender_session'] ?? '');
        $resolvedSession = self::resolveSessionUuid($sessionInput, $config);

        if (empty($recipientName)) {
            $recipientName = $isGroup ? 'Grup WhatsApp' : $cleanPhone;
        }

        // Build list of target URLs to try in priority order
        $urlsToTry = [];
        $customEndpoint = '/' . ltrim($config['endpoint_send'] ?: '/sendText', '/');

        // If user explicitly configured {session} in endpoint
        if (str_contains($customEndpoint, '{session}')) {
            $sess = $resolvedSession ?: ($sessionInput ?: 'default');
            $urlsToTry[] = $baseUrl . str_replace('{session}', $sess, $customEndpoint);
        } elseif (!empty($resolvedSession)) {
            // Direct OpenWA Multi-Session Endpoint (instant dispatch)
            $urlsToTry[] = "{$baseUrl}/api/sessions/{$resolvedSession}/messages/send-text";
        } else {
            // Standard single-session endpoints
            $urlsToTry[] = $baseUrl . $customEndpoint;
            $urlsToTry[] = "{$baseUrl}/api/sendText";
            $urlsToTry[] = "{$baseUrl}/sendText";
        }
        $urlsToTry = array_unique($urlsToTry);

        // Standard OpenWA payload
        $payload = [
            'chatId' => $chatId,
            'text' => $message,
        ];

        // Create initial log entry
        $waLog = WaLog::create([
            'phone' => $cleanPhone,
            'recipient_name' => $recipientName ?: $cleanPhone,
            'type' => $type,
            'message' => $message,
            'status' => 'pending',
        ]);

        $lastResponse = null;
        $lastResponseCode = null;
        $lastErrorMsg = '';

        foreach ($urlsToTry as $targetUrl) {
            try {
                $req = Http::withHeaders($headers)->timeout(10)->connectTimeout(3);
                if ($config['ssl_bypass'] ?? true) {
                    $req = $req->withoutVerifying();
                }
                $response = $req->post($targetUrl, $payload);
                $lastResponseCode = $response->status();
                $lastResponse = $response->json() ?? ['raw' => $response->body()];

                // If successful (HTTP 200, 201, 202)
                if ($response->successful()) {
                    $waLog->update([
                        'status' => 'success',
                        'response_code' => $lastResponseCode,
                        'response_data' => $lastResponse,
                        'error_message' => null,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Pesan WhatsApp berhasil terkirim melalui OpenWA.',
                        'log_id' => $waLog->id,
                        'response_code' => $lastResponseCode,
                        'data' => $lastResponse,
                        'url_used' => $targetUrl,
                    ];
                }

                // If 404, continue to next candidate URL
                if ($response->status() === 404) {
                    continue;
                }

                // If other error (e.g. 400 Bad Request, session not ready), break and report
                $errJson = $response->json();
                $rawMsg = $errJson['message'] ?? $response->body();
                if (is_array($rawMsg)) {
                    $rawMsg = implode(', ', $rawMsg);
                }
                $lastErrorMsg = "HTTP {$lastResponseCode}: {$rawMsg}";
                break;
            } catch (\Throwable $e) {
                $lastErrorMsg = $e->getMessage();
            }
        }

        $errorMsg = $lastErrorMsg ?: "HTTP {$lastResponseCode}: Endpoint OpenWA tidak ditemukan (404).";
        $waLog->update([
            'status' => 'failed',
            'response_code' => $lastResponseCode ?: 500,
            'response_data' => $lastResponse ?: ['error' => $lastErrorMsg],
            'error_message' => substr($errorMsg, 0, 490),
        ]);

        return [
            'success' => false,
            'message' => "Gagal mengirim pesan WhatsApp: {$errorMsg}",
            'log_id' => $waLog->id,
            'response_code' => $lastResponseCode,
            'data' => $lastResponse,
        ];
    }

    /**
     * Send automated notification for new registration submission.
     */
    public static function sendNewRegistrationNotification(Registration $registration): array
    {
        $config = self::getConfig();
        if (!$config['enabled'] || !$config['notify_new_registration']) {
            return ['success' => false, 'skipped' => true, 'message' => 'Notifikasi registrasi baru dinonaktifkan.'];
        }

        $results = [];

        // 1. Send to School Coordinator
        if (!empty($registration->mentor_wa) && $registration->mentor_wa !== '-') {
            $tpl = $config['tpl_new_registration'];
            $msg = self::parseTemplate($tpl, $registration);
            $results['coordinator'] = self::sendMessage(
                $registration->mentor_wa,
                $msg,
                'new_registration',
                $registration->mentor_name ?: $registration->school_name
            );
        }

        // 2. Send to Admin Panitia & WhatsApp Group (if enabled)
        if ($config['notify_admin_new_registration']) {
            $adminTpl = $config['tpl_admin_notification'];
            $adminMsg = self::parseTemplate($adminTpl, $registration);

            // A. Send to Admin Personal Numbers
            if (!empty($config['admin_notification_numbers'])) {
                $adminNumbers = array_filter(array_map('trim', explode(',', $config['admin_notification_numbers'])));
                $results['admins'] = [];
                foreach ($adminNumbers as $num) {
                    if (!empty($num)) {
                        $results['admins'][$num] = self::sendMessage(
                            $num,
                            $adminMsg,
                            'new_registration_admin',
                            'Admin Panitia'
                        );
                    }
                }
            }

            // B. Send to Admin WhatsApp Group (if configured & enabled)
            if ($config['notify_admin_group'] && !empty($config['admin_group_id'])) {
                $results['admin_group'] = self::sendMessage(
                    $config['admin_group_id'],
                    $adminMsg,
                    'new_registration_admin',
                    'Grup WhatsApp Panitia'
                );
            }
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * Send automated notification for registration/file verification or payment approval.
     */
    public static function sendVerificationNotification(Registration $registration, string $verificationType = 'registration'): array
    {
        $config = self::getConfig();
        if (!$config['enabled']) {
            return ['success' => false, 'skipped' => true, 'message' => 'WA Gateway nonaktif.'];
        }

        if ($verificationType === 'registration' && !$config['notify_verified_registration']) {
            return ['success' => false, 'skipped' => true, 'message' => 'Notifikasi verifikasi berkas dinonaktifkan.'];
        }

        if ($verificationType === 'payment' && !$config['notify_verified_payment']) {
            return ['success' => false, 'skipped' => true, 'message' => 'Notifikasi verifikasi pembayaran dinonaktifkan.'];
        }

        if (empty($registration->mentor_wa) || $registration->mentor_wa === '-') {
            return ['success' => false, 'skipped' => true, 'message' => 'Nomor WhatsApp koordinator tidak ditemukan.'];
        }

        $tpl = ($verificationType === 'payment')
            ? $config['tpl_verified_payment']
            : $config['tpl_verified_registration'];

        $msg = self::parseTemplate($tpl, $registration);
        $logType = ($verificationType === 'payment') ? 'verification_payment' : 'verification_registration';

        return self::sendMessage(
            $registration->mentor_wa,
            $msg,
            $logType,
            $registration->mentor_name ?: $registration->school_name
        );
    }

    /**
     * Parse placeholders in a template with actual registration and event data.
     */
    public static function parseTemplate(string $template, Registration $registration): string
    {
        $eventName = Setting::get('event_name', 'SEMARAK ALIYAH XIV 2026');
        $contactWa = Setting::get('contact_wa', '0838 5007 0767');
        $appUrl = rtrim(config('app.url', url('/')), '/');

        $statusUrl = $appUrl . '/layanan-registrasi';
        $adminPendaftarUrl = $appUrl . '/admin/pendaftar';

        $totalFeeFormatted = 'Rp ' . number_format($registration->total_fee ?? 0, 0, ',', '.');
        if (($registration->total_fee ?? 0) === 0 && $registration->package_type === 'all-in') {
            $totalFeeFormatted = 'Gratis (Rp 0 - Paket All-In)';
        }

        $packageLabel = ($registration->package_type === 'all-in')
            ? 'Paket All-In Komplit'
            : 'Paket Satuan (Per-Cabang)';

        $replacements = [
            '{nama_sekolah}' => $registration->school_name ?? '-',
            '{nama_koordinator}' => $registration->mentor_name ?? 'Koordinator Kontingen',
            '{mentor_wa}' => $registration->mentor_wa ?? '-',
            '{kode_pendaftaran}' => $registration->reg_code ?? '-',
            '{cabang_lomba}' => $registration->competition_title ?? '-',
            '{paket_pendaftaran}' => $packageLabel,
            '{status_pendaftaran}' => $registration->registration_status ?? 'Menunggu Verifikasi',
            '{status_pembayaran}' => $registration->payment_status ?? 'Menunggu Verifikasi',
            '{total_biaya}' => $totalFeeFormatted,
            '{nama_event}' => $eventName,
            '{kontak_panitia}' => $contactWa,
            '{url_status}' => $statusUrl,
            '{url_login}' => $appUrl . '/pendaftar/login',
            '{url_dashboard}' => $appUrl . '/pendaftar/dashboard',
            '{admin_pendaftar_url}' => $adminPendaftarUrl,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
