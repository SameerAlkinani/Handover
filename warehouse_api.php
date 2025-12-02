<?php
// warehouse_api.php
// API بسيط لتخزين وقراءة بيانات المنصة في ملف JSON واحد مشترك بين كل الأجهزة

header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/warehouse-data.json';

// GET = قراءة البيانات
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($file)) {
        echo json_encode([
            'users'            => [],
            'handovers'        => [],
            'deletedHandovers' => [],
            'accounts'         => [],
            'lastSerial'       => 0,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $content = file_get_contents($file);
    if ($content === false || $content === '') {
        echo json_encode([
            'users'            => [],
            'handovers'        => [],
            'deletedHandovers' => [],
            'accounts'         => [],
            'lastSerial'       => 0,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo $content;
    exit;
}

// POST = حفظ البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        http_response_code(400);
        echo json_encode(['error' => 'Empty body'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode($raw, true);
    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fp = fopen($file, 'c+');
    if (!$fp) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot open data file'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // قفل بسيط لمنع الكتابة المتزامنة
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
