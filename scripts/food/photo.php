<?php
// Zdjęcie dania (GET id = id produktu). Tylko dla tych, którzy widzą danie (dom, a udostępnione po akceptacji – wszyscy)
// oraz dla moderatorów bazy produktów (ocena dania w kolejce).
// Zdjęcie etykiety produktu (GET id, label=1) – tylko autor i moderatorzy (służy do weryfikacji wartości).
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security.php';
require_once 'food_lib.php';
session_write_close();

$id = (int)($_GET['id'] ?? 0);
$me = (int)$_SESSION['login_id'];
if (!empty($_GET['edit'])) {
    // zdjęcie etykiety z propozycji zmiany – autor propozycji i moderatorzy
    $e = $conn->query('SELECT user_id, label_photo FROM food_product_edits WHERE id = ' . (int)$_GET['edit'])->fetch_assoc();
    $file = $e && ((int)$e['user_id'] === $me || hl_can('food', 'read')) ? $e['label_photo'] : null;
} elseif (!empty($_GET['label'])) {
    $p = $conn->query("SELECT * FROM food_products WHERE id = $id")->fetch_assoc();
    if ($p && (int)$p['owner_id'] !== $me && !hl_can('food', 'read')) $p = null;
    $file = $p['label_photo'] ?? null;
} else {
    $p = hl_food_product($conn, $me, $id);
    if (!$p && hl_can('food', 'read')) $p = $conn->query("SELECT * FROM food_products WHERE id = $id")->fetch_assoc();
    $file = $p['photo'] ?? null;
}
$path = $file ? hl_food_photo_dir() . basename($file) : '';
$info = $path && is_file($path) ? @getimagesize($path) : false;
if (!$info) {
    http_response_code(404);
    exit();
}
header('Content-Type: ' . $info['mime']);
header('Content-Length: ' . filesize($path));
// adres zawiera wersję pliku – można trzymać długo
header('Cache-Control: private, max-age=2592000, immutable');
header('X-Content-Type-Options: nosniff');
readfile($path);
