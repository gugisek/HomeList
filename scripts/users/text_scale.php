<?php
// Rozmiar tekstu w całej aplikacji – POST scale = jeden z HL_TEXT_SCALES (procent) – JSON
header('Content-Type: application/json');
include '../security_scripts.php';
include '../conn_db.php';
require_once '../access.php';
require_once 'text_scale_lib.php';

$userId = (int)$_SESSION['login_id'];
$scale = (int)($_POST['scale'] ?? 0);
if (!isset(HL_TEXT_SCALES[$scale])) hl_json_exit('warning', 'Nieprawidłowy rozmiar tekstu');

$stmt = $conn->prepare('UPDATE users SET text_scale = ? WHERE id = ?');
$stmt->bind_param('ii', $scale, $userId);
if (!$stmt->execute()) hl_json_exit('error', 'Nie udało się zapisać ustawień');
hl_json_exit('success', 'Rozmiar tekstu: ' . mb_strtolower(HL_TEXT_SCALES[$scale]), ['scale' => $scale]);
