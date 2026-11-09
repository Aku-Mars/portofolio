<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$dbPath = __DIR__ . '/../../data/analytics.db';

try {
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Initialize tables if needed
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS visitor_counter (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            count INTEGER NOT NULL DEFAULT 0,
            updated_at TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS visitor_rate (
            ip TEXT PRIMARY KEY,
            last_visit_at INTEGER NOT NULL
        );
    ");

    // Ensure initial record
    $stmt = $pdo->query("SELECT count FROM visitor_counter WHERE id = 1");
    $row = $stmt->fetch();
    if (!$row) {
        $pdo->exec("INSERT INTO visitor_counter (id, count, updated_at) VALUES (1, 1851, datetime('now'))");
        $currentCount = 1851;
    } else {
        $currentCount = (int) $row['count'];
    }

    $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');

    if ($isPost) {
        // Resolve client IP (supporting Cloudflare / Proxy headers)
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '127.0.0.1';
        
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $now = time();
        $rateLimitSeconds = 3600; // 1 hour window per IP

        $rateStmt = $pdo->prepare("SELECT last_visit_at FROM visitor_rate WHERE ip = :ip");
        $rateStmt->execute([':ip' => $ip]);
        $rateRow = $rateStmt->fetch();

        $shouldCount = true;
        if ($rateRow && ($now - (int) $rateRow['last_visit_at']) < $rateLimitSeconds) {
            $shouldCount = false;
        }

        if ($shouldCount) {
            $pdo->beginTransaction();
            $pdo->exec("UPDATE visitor_counter SET count = count + 1, updated_at = datetime('now') WHERE id = 1");
            $saveRate = $pdo->prepare("INSERT OR REPLACE INTO visitor_rate (ip, last_visit_at) VALUES (:ip, :now)");
            $saveRate->execute([':ip' => $ip, ':now' => $now]);
            $pdo->commit();

            $currentCount++;
        }
    }

    echo json_encode([
        'status' => 'ok',
        'count' => $currentCount
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to process visitor count',
        'count' => 1851
    ]);
}
