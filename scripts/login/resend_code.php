<?php
// Ponowna wysyłka kodu aktywacyjnego – JSON. Odpowiedź nie zdradza, czy konto istnieje.
header('Content-Type: application/json');
require_once __DIR__ . '/../session_bootstrap.php';
require_once __DIR__ . '/../csrf.php';
include __DIR__ . '/../conn_db.php';
require_once __DIR__ . '/account_lib.php';

$generic = ['status' => 'success', 'message' => 'Jeśli konto czeka na aktywację, wysłaliśmy nowy kod na podany adres.'];

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify()) {
    echo json_encode(['status' => 'warning', 'message' => 'Formularz wygasł. Odśwież stronę i spróbuj ponownie.']);
    exit();
}

$email = trim((string)($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'warning', 'message' => 'Podaj poprawny adres email.']);
    exit();
}

$stmt = $conn->prepare('SELECT id, login FROM users WHERE LOWER(login) = LOWER(?) AND status_id = 2 LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode($generic);
    exit();
}
$userId = (int)$user['id'];

// 60 s przerwy między kodami i maks. 5 kodów na godzinę
$stmt = $conn->prepare('SELECT COUNT(*) AS cnt, MAX(created_at) AS last_at FROM activation_tokens WHERE user_id = ? AND created_at > NOW() - INTERVAL 1 HOUR');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($stats['last_at'] !== null && time() - strtotime($stats['last_at']) < 60) {
    $wait = 60 - (time() - strtotime($stats['last_at']));
    echo json_encode(['status' => 'warning', 'message' => 'Poczekaj ' . $wait . ' s przed ponownym wysłaniem kodu.']);
    exit();
}
if ((int)$stats['cnt'] >= 5) {
    echo json_encode(['status' => 'warning', 'message' => 'Wysłano już kilka kodów. Spróbuj ponownie za godzinę.']);
    exit();
}

$mail = hl_send_activation_code($conn, $userId, $user['login']);
if ($mail['status'] !== 'success') {
    error_log('resend_code mail error: ' . $mail['message']);
    echo json_encode(['status' => 'error', 'message' => 'Nie udało się wysłać maila. Skontaktuj się z administratorem.']);
    exit();
}
echo json_encode($generic);
