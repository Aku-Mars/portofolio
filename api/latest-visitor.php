<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=30');

$dataFile = __DIR__ . '/../../data/sentinel/latest_visitor.json';
$stateFile = __DIR__ . '/../../data/sentinel/sentinel_state.json';

$visitorData = null;

// 1. Prioritize explicit latest_visitor.json
if (file_exists($dataFile) && is_readable($dataFile)) {
    $raw = @file_get_contents($dataFile);
    $decoded = json_decode((string) $raw, true);
    if (is_array($decoded) && !empty($decoded['city']) && !empty($decoded['country']) && $decoded['city'] !== 'Unknown') {
        $visitorData = $decoded;
    }
}

// 2. Fallback to latest human profile from sentinel_state.json
if ($visitorData === null && file_exists($stateFile) && is_readable($stateFile)) {
    $rawState = @file_get_contents($stateFile);
    $state = json_decode((string) $rawState, true);
    if (is_array($state) && !empty($state['visitor_profiles'])) {
        $profiles = (array) $state['visitor_profiles'];
        // Search backwards for the most recent valid residential or city profile
        $validProfiles = [];
        foreach ($profiles as $p) {
            if (is_array($p) && !empty($p['city']) && !empty($p['country']) && $p['city'] !== 'Unknown' && $p['city'] !== 'Internal') {
                $validProfiles[] = $p;
            }
        }
        if (!empty($validProfiles)) {
            // Sort by timestamp desc
            usort($validProfiles, function ($a, $b) {
                return (int) ($b['ts'] ?? 0) <=> (int) ($a['ts'] ?? 0);
            });
            $chosen = $validProfiles[0];
            $visitorData = [
                'city' => $chosen['city'],
                'country' => $chosen['country'],
                'isp' => $chosen['isp'] ?? 'Broadband Network',
                'conn_badge' => $chosen['type'] ?? 'Residential',
                'timestamp' => (int) ($chosen['ts'] ?? time() - 300)
            ];
        }
    }
}

// 3. Fallback baseline if server just booted with 0 history
if ($visitorData === null) {
    $visitorData = [
        'city' => 'Jakarta',
        'country' => 'Indonesia',
        'isp' => 'Telkom Indonesia',
        'conn_badge' => 'Residential (Wi-Fi / Broadband • Orang Asli)',
        'timestamp' => time() - 180
    ];
}

$ts = (int) ($visitorData['timestamp'] ?? time());
$diff = max(0, time() - $ts);

if ($diff < 60) {
    $timeAgoId = 'Baru saja';
    $timeAgoEn = 'Just now';
} elseif ($diff < 3600) {
    $m = floor($diff / 60);
    $timeAgoId = "{$m}m lalu";
    $timeAgoEn = "{$m}m ago";
} elseif ($diff < 86400) {
    $h = floor($diff / 3600);
    $timeAgoId = "{$h}j lalu";
    $timeAgoEn = "{$h}h ago";
} else {
    $d = floor($diff / 86400);
    $timeAgoId = "{$d}h lalu";
    $timeAgoEn = "{$d}d ago";
}

echo json_encode([
    'status' => 'ok',
    'visitor' => [
        'city' => htmlspecialchars((string) ($visitorData['city'] ?? 'Jakarta'), ENT_QUOTES, 'UTF-8'),
        'country' => htmlspecialchars((string) ($visitorData['country'] ?? 'Indonesia'), ENT_QUOTES, 'UTF-8'),
        'conn_badge' => htmlspecialchars((string) ($visitorData['conn_badge'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'service' => htmlspecialchars((string) ($visitorData['service'] ?? 'Portofolio Mars'), ENT_QUOTES, 'UTF-8'),
        'timestamp' => $ts,
        'timeAgo' => $timeAgoId,
        'timeAgo_id' => $timeAgoId,
        'timeAgo_en' => $timeAgoEn
    ]
], JSON_UNESCAPED_UNICODE);
