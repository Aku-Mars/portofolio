<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$requestSite = strtolower($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$requestHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$originHost = $requestOrigin !== '' ? strtolower(parse_url($requestOrigin, PHP_URL_HOST) ?? '') : '';
if ($requestSite === 'cross-site' || ($originHost !== '' && !hash_equals($requestHost, $originHost))) {
    http_response_code(403);
    echo json_encode(['error' => 'Cross-site request denied']);
    exit;
}

$webhookUrl = getenv('DISCORD_WEBHOOK_URL') ?: '';
if (!preg_match('#^https://(?:canary\.|ptb\.)?discord(?:app)?\.com/api/webhooks/#i', $webhookUrl)) {
    error_log('DISCORD_WEBHOOK_URL is missing or invalid.');
    http_response_code(503);
    echo json_encode(['error' => 'Notification service is not configured']);
    exit;
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 16384) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

$input = json_decode($rawBody, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

function text_value($value, int $maxLength = 300): string
{
    if (!is_string($value)) {
        return '';
    }

    $value = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '');
    return function_exists('mb_substr')
        ? mb_substr($value, 0, $maxLength)
        : substr($value, 0, $maxLength);
}

function display_value($value): string
{
    $value = text_value($value);
    return $value !== '' ? $value : 'Unknown';
}

function client_ip(): string
{
    // REMOTE_ADDR tidak mempercayai header spoofable dari klien.
    return text_value($_SERVER['REMOTE_ADDR'] ?? '', 64);
}

$clientIp = client_ip();
$rateFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'portfolio-discord-' . hash('sha256', $clientIp ?: 'unknown') . '.rate';
$rateHandle = @fopen($rateFile, 'c+');
if ($rateHandle !== false) {
    flock($rateHandle, LOCK_EX);
    $lastSentAt = (int) stream_get_contents($rateHandle);
    if ($lastSentAt > 0 && time() - $lastSentAt < 900) {
        flock($rateHandle, LOCK_UN);
        fclose($rateHandle);
        http_response_code(429);
        echo json_encode(['status' => 'rate_limited']);
        exit;
    }
}

$location = is_array($input['location'] ?? null) ? $input['location'] : [];
$network = is_array($input['network'] ?? null) ? $input['network'] : [];
$device = is_array($input['device'] ?? null) ? $input['device'] : [];
$traffic = is_array($input['traffic'] ?? null) ? $input['traffic'] : [];

$reportedIp = text_value($network['ip'] ?? '', 64);
$ip = $reportedIp !== '' ? $reportedIp : $clientIp;
$source = display_value($traffic['source'] ?? 'Direct');
$referrer = text_value($traffic['referrer'] ?? '', 700);
$landingPage = text_value($traffic['landingPage'] ?? '', 500);
$utmSource = text_value($traffic['utmSource'] ?? '');
$utmMedium = text_value($traffic['utmMedium'] ?? '');
$utmCampaign = text_value($traffic['utmCampaign'] ?? '');
$campaign = array_filter([
    $utmSource !== '' ? "Source: {$utmSource}" : '',
    $utmMedium !== '' ? "Medium: {$utmMedium}" : '',
    $utmCampaign !== '' ? "Campaign: {$utmCampaign}" : '',
]);

$fields = [
    ['name' => 'Sumber Kunjungan', 'value' => "**Dari:** {$source}\n**Referrer:** " . ($referrer ?: 'Direct / tidak tersedia'), 'inline' => false],
    ['name' => 'Halaman Masuk', 'value' => $landingPage ?: '/', 'inline' => false],
    ['name' => 'Waktu Akses', 'value' => display_value($input['accessedAt'] ?? ''), 'inline' => false],
    ['name' => 'Lokasi', 'value' => '**Kota:** ' . display_value($location['city'] ?? '') . "\n**Wilayah:** " . display_value($location['region'] ?? '') . "\n**Negara:** " . display_value($location['country'] ?? ''), 'inline' => true],
    ['name' => 'Jaringan', 'value' => '**Alamat IP:** ' . display_value($ip) . "\n**ISP:** " . display_value($network['isp'] ?? ''), 'inline' => true],
    ['name' => 'Info Perangkat', 'value' => '**Tipe:** ' . display_value($device['type'] ?? '') . "\n**OS:** " . display_value($device['os'] ?? '') . "\n**Browser:** " . display_value($device['browser'] ?? ''), 'inline' => true],
];

if ($campaign !== []) {
    $fields[] = ['name' => 'UTM Campaign', 'value' => implode("\n", $campaign), 'inline' => false];
}

$userAgent = text_value($device['userAgent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''), 900);
if ($userAgent !== '') {
    $fields[] = ['name' => 'User Agent', 'value' => "```{$userAgent}```", 'inline' => false];
}

$discordPayload = json_encode([
    'allowed_mentions' => ['parse' => []],
    'embeds' => [[
        'title' => 'Pengunjung Baru di Portofolio',
        'description' => "Seseorang mengakses portofolio dari **{$source}**.",
        'color' => 0x2dd4bf,
        'fields' => $fields,
        'footer' => ['text' => 'Portfolio Visitor Notification'],
        'timestamp' => gmdate('c'),
    ]],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$curl = curl_init($webhookUrl);
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => $discordPayload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 5,
]);
curl_exec($curl);
$curlError = curl_error($curl);
$statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);

if ($statusCode < 200 || $statusCode >= 300) {
    if ($rateHandle !== false) {
        flock($rateHandle, LOCK_UN);
        fclose($rateHandle);
    }
    error_log("Discord visitor webhook failed with HTTP {$statusCode}: {$curlError}");
    http_response_code(502);
    echo json_encode(['error' => 'Discord webhook failed']);
    exit;
}

if ($rateHandle !== false) {
    ftruncate($rateHandle, 0);
    rewind($rateHandle);
    fwrite($rateHandle, (string) time());
    fflush($rateHandle);
    flock($rateHandle, LOCK_UN);
    fclose($rateHandle);
}

http_response_code(204);
