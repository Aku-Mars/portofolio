<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=30');

$dataFile = __DIR__ . '/../../data/sentinel/latest_visitor.json';
$stateFile = __DIR__ . '/../../data/sentinel/sentinel_state.json';

$visitorData = null;
$validProfiles = [];

// 1. Prioritize explicit latest_visitor.json
if (file_exists($dataFile) && is_readable($dataFile)) {
    $raw = @file_get_contents($dataFile);
    $decoded = json_decode((string) $raw, true);
    if (is_array($decoded) && !empty($decoded['city']) && !empty($decoded['country']) && $decoded['city'] !== 'Unknown') {
        $visitorData = $decoded;
    }
}

// 2. Extract valid human profiles from sentinel_state.json
if (file_exists($stateFile) && is_readable($stateFile)) {
    $rawState = @file_get_contents($stateFile);
    $state = json_decode((string) $rawState, true);
    if (is_array($state) && !empty($state['visitor_profiles'])) {
        $profiles = (array) $state['visitor_profiles'];
        foreach ($profiles as $p) {
            if (is_array($p) && !empty($p['city']) && !empty($p['country']) && $p['city'] !== 'Unknown' && $p['city'] !== 'Internal') {
                $validProfiles[] = $p;
            }
        }
        if (!empty($validProfiles)) {
            usort($validProfiles, function ($a, $b) {
                return (int) ($b['ts'] ?? 0) <=> (int) ($a['ts'] ?? 0);
            });
            if ($visitorData === null) {
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
}

// 3. Fallback baseline if server has no data
if ($visitorData === null) {
    $visitorData = [
        'city' => 'Springfield',
        'country' => 'United States',
        'isp' => 'Broadband Network',
        'conn_badge' => 'Residential',
        'timestamp' => time() - 60
    ];
}

function format_time_diff(int $ts): array {
    $diff = max(0, time() - $ts);
    if ($diff < 60) {
        return ['Baru saja', 'Just now'];
    } elseif ($diff < 3600) {
        $m = floor($diff / 60);
        return ["{$m}m lalu", "{$m}m ago"];
    } elseif ($diff < 86400) {
        $h = floor($diff / 3600);
        return ["{$h}j lalu", "{$h}h ago"];
    } else {
        $d = floor($diff / 86400);
        return ["{$d}h lalu", "{$d}d ago"];
    }
}

$ts = (int) ($visitorData['timestamp'] ?? time());
[$timeAgoId, $timeAgoEn] = format_time_diff($ts);

$primaryVisitor = [
    'city' => htmlspecialchars((string) ($visitorData['city'] ?? 'Jakarta'), ENT_QUOTES, 'UTF-8'),
    'country' => htmlspecialchars((string) ($visitorData['country'] ?? 'Indonesia'), ENT_QUOTES, 'UTF-8'),
    'conn_badge' => htmlspecialchars((string) ($visitorData['conn_badge'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'service' => htmlspecialchars((string) ($visitorData['service'] ?? 'Portofolio Mars'), ENT_QUOTES, 'UTF-8'),
    'timestamp' => $ts,
    'timeAgo' => $timeAgoId,
    'timeAgo_id' => $timeAgoId,
    'timeAgo_en' => $timeAgoEn
];

$visitorsList = [$primaryVisitor];

// Add other recent profiles if available
if (!empty($validProfiles)) {
    foreach ($validProfiles as $vp) {
        if (count($visitorsList) >= 6) break;
        $city = (string) ($vp['city'] ?? '');
        $country = (string) ($vp['country'] ?? '');
        $exists = false;
        foreach ($visitorsList as $vItem) {
            if ($vItem['city'] === $city) { $exists = true; break; }
        }
        if (!$exists && !empty($city)) {
            $vts = (int) ($vp['ts'] ?? time() - (count($visitorsList) * 240));
            [$agoId, $agoEn] = format_time_diff($vts);
            $visitorsList[] = [
                'city' => htmlspecialchars($city, ENT_QUOTES, 'UTF-8'),
                'country' => htmlspecialchars($country, ENT_QUOTES, 'UTF-8'),
                'conn_badge' => htmlspecialchars((string) ($vp['type'] ?? 'Residential'), ENT_QUOTES, 'UTF-8'),
                'service' => 'Portofolio Mars',
                'timestamp' => $vts,
                'timeAgo' => $agoId,
                'timeAgo_id' => $agoId,
                'timeAgo_en' => $agoEn
            ];
        }
    }
}

// Fallback diverse locations if list is small
$fallbacks = [
    ['city' => 'Jakarta', 'country' => 'Indonesia', 'offset' => 320],
    ['city' => 'Singapore', 'country' => 'Singapore', 'offset' => 740],
    ['city' => 'Bandung', 'country' => 'Indonesia', 'offset' => 1500],
    ['city' => 'Frankfurt', 'country' => 'Germany', 'offset' => 2800],
    ['city' => 'Tokyo', 'country' => 'Japan', 'offset' => 4200],
];
foreach ($fallbacks as $fb) {
    if (count($visitorsList) >= 6) break;
    $exists = false;
    foreach ($visitorsList as $vItem) {
        if ($vItem['city'] === $fb['city']) { $exists = true; break; }
    }
    if (!$exists) {
        $fts = time() - $fb['offset'];
        [$agoId, $agoEn] = format_time_diff($fts);
        $visitorsList[] = [
            'city' => $fb['city'],
            'country' => $fb['country'],
            'conn_badge' => 'Residential',
            'service' => 'Portofolio Mars',
            'timestamp' => $fts,
            'timeAgo' => $agoId,
            'timeAgo_id' => $agoId,
            'timeAgo_en' => $agoEn
        ];
    }
}

echo json_encode([
    'status' => 'ok',
    'visitor' => $primaryVisitor,
    'visitors' => $visitorsList
], JSON_UNESCAPED_UNICODE);
