<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function json_response(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function env_value(string $key): string {
    $value = getenv($key);
    return $value === false ? '' : trim($value);
}

function http_json(string $url, string $method, array $headers, ?array $body = null): array {
    $ch = curl_init($url);
    if ($ch === false) throw new RuntimeException('HTTP client unavailable.');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADER => false
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($raw === false) throw new RuntimeException($error ?: 'Supabase request failed.');
    return ['status' => $status, 'json' => json_decode((string)$raw, true), 'raw' => (string)$raw];
}

$path = trim((string)($_GET['path'] ?? ''), '/');
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$supabaseUrl = rtrim(env_value('SUPABASE_URL'), '/');
$publishableKey = env_value('SUPABASE_PUBLISHABLE_KEY');

if ($path === '' || $path === 'health') {
    json_response([
        'ok' => true,
        'service' => 'nova-cms-php-api',
        'version' => '2.0.0',
        'php' => PHP_VERSION,
        'supabase_configured' => $supabaseUrl !== '' && $publishableKey !== ''
    ]);
}

if ($path === 'reports' && $method === 'POST') {
    if ($supabaseUrl === '' || $publishableKey === '') json_response(['error' => 'Supabase server configuration missing.'], 503);
    $authorization = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $match)) json_response(['error' => 'Bearer token required.'], 401);
    $jwt = trim($match[1]);

    try {
        $userResponse = http_json($supabaseUrl . '/auth/v1/user', 'GET', [
            'apikey: ' . $publishableKey,
            'Authorization: Bearer ' . $jwt
        ]);
        $userId = (string)($userResponse['json']['id'] ?? '');
        if ($userResponse['status'] !== 200 || $userId === '') json_response(['error' => 'Invalid session.'], 401);

        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) json_response(['error' => 'Invalid JSON.'], 400);
        $type = (string)($input['resource_type'] ?? '');
        $resourceId = (string)($input['resource_id'] ?? '');
        $reason = trim((string)($input['reason'] ?? ''));
        if (!in_array($type, ['thread','reply','post','profile'], true) || !preg_match('/^[0-9a-f-]{36}$/i', $resourceId) || strlen($reason) < 3 || strlen($reason) > 1000) {
            json_response(['error' => 'Invalid report payload.'], 422);
        }

        $insert = http_json($supabaseUrl . '/rest/v1/reports', 'POST', [
            'apikey: ' . $publishableKey,
            'Authorization: Bearer ' . $jwt,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ], [
            'reporter_id' => $userId,
            'resource_type' => $type,
            'resource_id' => $resourceId,
            'reason' => $reason
        ]);

        if ($insert['status'] < 200 || $insert['status'] >= 300) json_response(['error' => 'Report rejected by database policy.'], 400);
        json_response(['ok' => true, 'report' => $insert['json'][0] ?? null], 201);
    } catch (Throwable $e) {
        json_response(['error' => 'Backend request failed.'], 502);
    }
}

json_response(['error' => 'Not found.'], 404);
