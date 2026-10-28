<?php
// "Nie pamiętam hasła" – JSON. Odpowiedź jest zawsze taka sama niezależnie od tego, czy konto
// istnieje albo trafiło na limit – inaczej endpoint pozwalałby sprawdzać zarejestrowane adresy.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../session_bootstrap.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../login_attempts.php';
require_once __DIR__ . '/../mailer.php';
require_once __DIR__ . '/../log_lib.php';
include __DIR__ . '/../conn_db.php';

$generic = ['status' => 'success', 'message' => 'Jeśli konto istnieje, wysłaliśmy link do ustawienia nowego hasła na podany adres.'];

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify()) {
    echo json_encode(['status' => 'warning', 'message' => 'Formularz wygasł. Odśwież stronę i spróbuj ponownie.']);
    exit();
}

$email = trim((string)($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'warning', 'message' => 'Podaj poprawny adres email.']);
    exit();
}
if (!hl_mail_configured($conn)) {
    echo json_encode(['status' => 'warning', 'message' => 'Wysyłka maili nie jest skonfigurowana. Poproś administratora o zmianę hasła.']);
    exit();
}

$ip = hl_client_ip();
// limit per IP niezależny od istnienia konta (spam / DoS na skrzynkę nadawczą)
if ($ip !== null) {
    $stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM password_reset_tokens WHERE requester_ip = ? AND created_at > NOW() - INTERVAL 1 HOUR');
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $ipCount = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
    if ($ipCount >= 20) {
        echo json_encode($generic);
        exit();
    }
}

$stmt = $conn->prepare('SELECT id, login, mail, status_id FROM users WHERE LOWER(login) = LOWER(?) LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || (int)$user['status_id'] !== 1) {
    echo json_encode($generic);
    exit();
}
$userId = (int)$user['id'];

// 60 s przerwy i maks. 5 linków na godzinę na konto
$stmt = $conn->prepare('SELECT COUNT(*) AS cnt, MAX(created_at) AS last_at FROM password_reset_tokens WHERE user_id = ? AND created_at > NOW() - INTERVAL 1 HOUR');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (($stats['last_at'] !== null && time() - strtotime($stats['last_at']) < 60) || (int)$stats['cnt'] >= 5) {
    echo json_encode($generic);
    exit();
}

$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$expiresAt = date('Y-m-d H:i:s', time() + 45 * 60);
$stmt = $conn->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, requester_ip) VALUES (?, ?, ?, ?)');
$stmt->bind_param('isss', $userId, $tokenHash, $expiresAt, $ip);
$stmt->execute();
$stmt->close();

$recipient = filter_var($user['mail'], FILTER_VALIDATE_EMAIL) ? $user['mail'] : $user['login'];
$resetUrl = appBaseUrl() . 'reset_password?token=' . urlencode($token);
$content = '<p>Otrzymaliśmy prośbę o ustawienie nowego hasła do Twojego konta HomeList.</p>'
    . hl_mail_button($resetUrl, 'Ustaw nowe hasło')
    . '<p>Link jest ważny przez 45 minut i działa tylko raz.</p>'
    . "<p style='color:#6b7280;'>Jeśli to nie Ty, zignoruj tę wiadomość – hasło pozostanie bez zmian.</p>";
$mail = send_app_mail(['to' => $recipient, 'subject' => 'Reset hasła HomeList', 'body' => $content]);
if ($mail['status'] !== 'success') {
    error_log('forgot_password mail error: ' . $mail['message']);
}

hl_log($conn, $userId, 'users', '', 'Email: ' . $recipient, 1, 'Żądanie resetu hasła', $userId);
echo json_encode($generic);
