<?php
// Zapamiętane urządzenia (remember_me) w profilu: wylogowanie jednego albo wszystkich pozostałych – JSON
header('Content-Type: application/json');
include '../security_scripts.php';
include '../conn_db.php';
require_once '../access.php';
require_once '../log_lib.php';

$userId = (int)$_SESSION['login_id'];
$action = $_POST['action'] ?? '';

if ($action === 'revoke') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare('SELECT selector FROM remember_tokens WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        hl_json_exit('error', 'Nie znaleziono urządzenia.');
    }
    if ($row['selector'] === hl_current_remember_selector()) {
        hl_json_exit('warning', 'To jest bieżące urządzenie – użyj przycisku Wyloguj.');
    }
    $stmt = $conn->prepare('DELETE FROM remember_tokens WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    hl_log($conn, $userId, 'users', '', 'Usunięto zapamiętane urządzenie #' . $id, 1, 'Wylogowano urządzenie');
    hl_json_exit('success', 'Urządzenie zostało wylogowane.');
}

if ($action === 'revoke_others') {
    hl_revoke_user_sessions($conn, $userId, true);
    hl_log($conn, $userId, 'users', '', 'Wylogowano pozostałe urządzenia', 1, 'Wylogowano pozostałe urządzenia');
    hl_json_exit('success', 'Wylogowano wszystkie pozostałe urządzenia.');
}

hl_json_exit('warning', 'Nieznana akcja.');
