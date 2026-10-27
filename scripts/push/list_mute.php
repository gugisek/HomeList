<?php
// Włączenie / wyłączenie powiadomień o nowych zadaniach na liście wspólnej (POST list_id, mute=0|1)
header('Content-Type: application/json');
include '../security_scripts.php';
include '../conn_db.php';
include '../access.php';

$user_id = (int)$_SESSION['login_id'];
$list_id = (int)($_POST['list_id'] ?? 0);
if (!hl_list_access($conn, $user_id, $list_id)) {
    hl_json_exit('error', 'Nie znaleziono listy');
}
if (!empty($_POST['mute'])) {
    $stmt = $conn->prepare('INSERT IGNORE INTO push_list_mute (user_id, list_id) VALUES (?, ?)');
    $message = 'Nie będziemy powiadamiać o nowych zadaniach na tej liście';
} else {
    $stmt = $conn->prepare('DELETE FROM push_list_mute WHERE user_id = ? AND list_id = ?');
    $message = 'Powiadomienia o nowych zadaniach włączone';
}
$stmt->bind_param('ii', $user_id, $list_id);
$stmt->execute();
hl_json_exit('success', $message);
