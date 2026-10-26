<?php
// Wydaje zrzut ekranu ze zgłoszenia: administrator zgłoszeń albo autor zgłoszenia. ?id=zgłoszenie&n=indeks
include '../security.php';
require_once 'report_lib.php';

$id = (int)($_GET['id'] ?? 0);
$n = (int)($_GET['n'] ?? 0);
$stmt = $conn->prepare('SELECT user_id, screenshots FROM bug_reports WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$shots = $row ? hl_report_shots($row['screenshots']) : [];

if (!$row || !isset($shots[$n]) || (!hl_can('reports', 'read') && (int)$row['user_id'] !== (int)$_SESSION['login_id'])) {
    http_response_code(404);
    exit();
}

$path = hl_report_dir() . basename($shots[$n]);
$info = is_file($path) ? @getimagesize($path) : false;
if (!$info) {
    http_response_code(404);
    exit();
}
header('Content-Type: ' . $info['mime']);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($path);
