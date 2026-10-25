<?php
// Zmiana własnego hasła (profil) – JSON. Inne urządzenia zostają wylogowane, bieżąca sesja zostaje.
header('Content-Type: application/json; charset=utf-8');
include '../security_scripts.php';
include '../conn_db.php';
require_once '../password_helper.php';
require_once '../access.php';
require_once '../log_lib.php';

$userId = (int)$_SESSION['login_id'];
$current = (string)($_POST['current_password'] ?? '');
$new = (string)($_POST['new_password'] ?? '');
$repeat = (string)($_POST['repeat_new_password'] ?? '');

if ($current === '' || $new === '' || $repeat === '') {
    hl_json_exit('warning', 'Uzupełnij wszystkie pola hasła.');
}
if ($new !== $repeat) {
    hl_json_exit('warning', 'Nowe hasła nie są takie same.');
}
if (($policy = passwordPolicyViolation($new)) !== null) {
    hl_json_exit('warning', $policy);
}

$stmt = $conn->prepare('SELECT pswd FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stored = (string)($stmt->get_result()->fetch_assoc()['pswd'] ?? '');
if (!verifyPassword($current, $stored)) {
    hl_json_exit('error', 'Aktualne hasło jest nieprawidłowe.');
}
if (verifyPassword($new, $stored)) {
    hl_json_exit('warning', 'Nowe hasło musi różnić się od aktualnego.');
}

$hash = hashPassword($new);
$stmt = $conn->prepare('UPDATE users SET pswd = ? WHERE id = ?');
$stmt->bind_param('si', $hash, $userId);
if (!$stmt->execute()) {
    hl_json_exit('error', 'Nie udało się zapisać nowego hasła.');
}
hl_revoke_user_sessions($conn, $userId, true);
hl_log($conn, $userId, 'users', '', 'Hasło zmienione', 1, 'Zmiana hasła (profil)');
hl_json_exit('success', 'Hasło zostało zmienione. Pozostałe urządzenia zostały wylogowane.');
