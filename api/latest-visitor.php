<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=30');

$dataFile = __DIR__ . '/../../data/sentinel/latest_visitor.json';

if (!file_exists($dataFile) || !is_readable($dataFile)) {
    echo json_encode([
        'status' => 'empty',
        'visitor' => null
    ]);
    exit;
}

$raw = file_get_contents($dataFile);
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode([
        'status' => 'empty',
        'visitor' => null
    ]);
    exit;
}

$ts = (int) ($data['timestamp'] ?? time());
$diff = max(0, time() - $ts);

if ($diff < 60) {
    $timeAgo = 'Baru saja';
} elseif ($diff < 3600) {
    $timeAgo = floor($diff / 60) . 'm lalu';
} elseif ($diff < 86400) {
    $timeAgo = floor($diff / 3600) . 'j lalu';
} else {
    $timeAgo = floor($diff / 86400) . 'h lalu';
}

echo json_encode([
    'status' => 'ok',
    'visitor' => [
        'city' => htmlspecialchars($data['city'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
        'country' => htmlspecialchars($data['country'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
        'conn_badge' => htmlspecialchars($data['conn_badge'] ?? '', ENT_QUOTES, 'UTF-8'),
        'service' => htmlspecialchars($data['service'] ?? 'Portofolio Mars', ENT_QUOTES, 'UTF-8'),
        'timestamp' => $ts,
        'timeAgo' => $timeAgo
    ]
], JSON_UNESCAPED_UNICODE);
