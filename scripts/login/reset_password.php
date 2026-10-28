<?php
// Ustawienie nowego hasła z linku z maila – JSON. Po resecie konto jest wylogowane ze wszystkich urządzeń.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../auth_lib.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../password_helper.php';
require_once __DIR__ . '/../mailer.php';
require_once __DIR__ . '/../log_lib.php';
include __DIR__ . '/../conn_db.php';

function hl_reset_json(string $status, string $message, array $extra = []): void
{
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify()) {
    hl_reset_json('warning', 'Formularz wygasł. Odśwież stronę i spróbuj ponownie.');
}

$token = trim((string)($_POST['token'] ?? ''));
$newPassword = (string)($_POST['new_password'] ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');

if ($token === '' || $newPassword === '' || $confirm === '') {
    hl_reset_json('warning', 'Wszystkie pola są wymagane.');
}
if ($newPassword !== $confirm) {
    hl_reset_json('warning', 'Hasła nie są identyczne.');
}
if (($policy = passwordPolicyViolation($newPassword)) !== null) {
    hl_reset_json('warning', $policy);
}

$tokenHash = hash('sha256', $token);
// atomowe "zajęcie" tokenu – ten sam link wysłany dwa razy naraz (skaner poczty + kliknięcie) zadziała raz
$stmt = $conn->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()');
$stmt->bind_param('s', $tokenHash);
$stmt->execute();
$claimed = $stmt->affected_rows === 1;
$stmt->close();
if (!$claimed) {
    hl_reset_json('error', 'Link jest nieprawidłowy lub wygasł. Wyślij prośbę o reset ponownie.');
}

$stmt = $conn->prepare('SELECT user_id FROM password_reset_tokens WHERE token_hash = ? LIMIT 1');
$stmt->bind_param('s', $tokenHash);
$stmt->execute();
$userId = (int)($stmt->get_result()->fetch_assoc()['user_id'] ?? 0);
$stmt->close();

$user = $userId > 0 ? hl_auth_user($conn, 'id', $userId) : null;
if (!$user) {
    hl_reset_json('error', 'Link jest nieprawidłowy lub wygasł. Wyślij prośbę o reset ponownie.');
}

$newHash = hashPassword($newPassword);
$stmt = $conn->prepare('UPDATE users SET pswd = ? WHERE id = ?');
$stmt->bind_param('si', $newHash, $userId);
$stmt->execute();
$stmt->close();

// pozostałe nieużyte linki tego konta przestają działać
$stmt = $conn->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->close();

hl_revoke_user_sessions($conn, $userId);

$recipient = filter_var($user['mail'], FILTER_VALIDATE_EMAIL) ? $user['mail'] : $user['login'];
$mail = send_app_mail([
    'to' => $recipient,
    'subject' => 'Hasło HomeList zostało zmienione',
    'body' => '<p>Hasło do Twojego konta HomeList zostało właśnie zmienione.</p>'
        . '<p>Ze względów bezpieczeństwa wylogowaliśmy wszystkie sesje i zapamiętane urządzenia.</p>'
        . "<p style='color:#6b7280;'>Jeśli to nie Ty, natychmiast zresetuj hasło ponownie i skontaktuj się z administratorem.</p>",
]);
if ($mail['status'] !== 'success') error_log('reset_password mail error: ' . $mail['message']);

hl_log($conn, $userId, 'users', '', 'Hasło zresetowane linkiem z maila', 1, 'Reset hasła przez email', $userId);

$_SESSION['alert'] = 'Hasło zostało zmienione. Zaloguj się nowym hasłem.';
$_SESSION['alert_type'] = 'success';
hl_reset_json('success', 'Hasło zostało zmienione.', ['redirect' => 'login']);
