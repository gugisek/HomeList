<?php
// Aktywacja konta 6-cyfrowym kodem z maila (AJAX z activate.php) – JSON.
header('Content-Type: application/json');
require_once __DIR__ . '/../session_bootstrap.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../login_attempts.php';
require_once __DIR__ . '/../log_lib.php';
include __DIR__ . '/../conn_db.php';

function hl_act_json(string $status, string $message, array $extra = []): void
{
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify()) {
    hl_act_json('warning', 'Formularz wygasł. Odśwież stronę i spróbuj ponownie.');
}

$email = trim((string)($_POST['email'] ?? ''));
$code = preg_replace('/\D/', '', (string)($_POST['code'] ?? ''));
if ($email === '' || strlen($code) !== 6) {
    hl_act_json('warning', 'Podaj adres email i 6-cyfrowy kod.');
}

// kod to tylko 6 cyfr – bez limitu dałoby się go zgadnąć w czasie ważności
if (hl_failed_attempts($conn, 'login', $email, ['activation']) >= 5) {
    hl_act_json('error', 'Zbyt wiele nieudanych prób. Poczekaj 15 minut lub poproś o nowy kod.');
}

$stmt = $conn->prepare(
    'SELECT at.id, at.user_id, at.expires_at FROM activation_tokens at
     JOIN users ON users.id = at.user_id
     WHERE LOWER(at.email) = LOWER(?) AND at.token = ? AND at.used = 0 AND users.status_id = 2
     ORDER BY at.created_at DESC LIMIT 1'
);
$stmt->bind_param('ss', $email, $code);
$stmt->execute();
$token = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$token) {
    record_login_attempt($conn, null, $email, false, 'activation', 'invalid_code');
    hl_act_json('error', 'Nieprawidłowy kod aktywacyjny.');
}
if (strtotime($token['expires_at']) < time()) {
    hl_act_json('error', 'Kod aktywacyjny wygasł. Wyślij nowy kod.');
}

$userId = (int)$token['user_id'];
$tokenId = (int)$token['id'];
$stmt = $conn->prepare('UPDATE users SET status_id = 1 WHERE id = ? AND status_id = 2');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->close();
$stmt = $conn->prepare('UPDATE activation_tokens SET used = 1 WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->close();

record_login_attempt($conn, $userId, $email, true, 'activation');
hl_log($conn, $userId, 'users', 'Status: nieaktywne', 'Status: aktywne', 1, 'Aktywowano konto kodem z maila', $userId);

$_SESSION['alert'] = 'Konto zostało aktywowane! Możesz się teraz zalogować.';
$_SESSION['alert_type'] = 'success';
hl_act_json('success', 'Konto zostało aktywowane!', ['redirect' => 'login.php']);
