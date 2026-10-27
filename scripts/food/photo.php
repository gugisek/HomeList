<?php
// Zdjęcie dania (GET id = id produktu). Tylko dla tych, którzy widzą danie (dom, a udostępnione po akceptacji – wszyscy)
// oraz dla moderatorów bazy produktów (ocena dania w kolejce).
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security.php';
require_once 'food_lib.php';
session_write_close();

$id = (int)($_GET['id'] ?? 0);
$p = hl_food_product($conn, (int)$_SESSION['login_id'], $id);
if (!$p && hl_can('food', 'read')) $p = $conn->query("SELECT * FROM food_products WHERE id = $id")->fetch_assoc();
$path = $p && $p['photo'] ? hl_food_photo_dir() . basename($p['photo']) : '';
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
