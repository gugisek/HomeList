<?php
// Logowanie hasłem (formularz components/login/login_form.php).
// Throttling brute-force z login_attempts: 5 nieudanych prób na konto i 20 na IP w 15 minut.
require_once __DIR__ . '/../auth_lib.php';
require_once __DIR__ . '/../password_helper.php';
require_once __DIR__ . '/../csrf.php';
include __DIR__ . '/../conn_db.php';

function hl_login_fail(string $message, string $type = 'error', string $to = '../../login.php'): void
{
    $_SESSION['alert'] = $message;
    $_SESSION['alert_type'] = $type;
    header('Location: ' . $to);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ../../login.php');
    exit();
}
if (!csrf_verify()) {
    hl_login_fail('Formularz wygasł. Spróbuj zalogować się ponownie.', 'warning');
}

$login = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$remember = isset($_POST['remember-me']);
$ip = hl_client_ip();

if ($login === '' || $password === '') {
    hl_login_fail('Podaj adres email i hasło.', 'warning');
}

// nieudane próby sprzed ostatniego resetu hasła tego konta się nie liczą – inaczej poprawny reset
// i tak zostawiałby użytkownika z blokadą, aż stare wpisy wypadną z okna 15 minut
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM login_attempts la
     WHERE LOWER(la.login_input) = LOWER(?) AND la.success = 0 AND la.method = 'password'
       AND la.created_at > NOW() - INTERVAL 15 MINUTE
       AND la.created_at > COALESCE((SELECT MAX(prt.used_at) FROM password_reset_tokens prt
                                     JOIN users u ON u.id = prt.user_id
                                     WHERE LOWER(u.login) = LOWER(?) AND prt.used_at IS NOT NULL), '1970-01-01')"
);
$stmt->bind_param('ss', $login, $login);
$stmt->execute();
$accountFails = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

if ($accountFails >= 5) {
    record_login_attempt($conn, null, $login, false, 'password', 'account_locked');
    hl_login_fail('Zbyt wiele nieudanych prób logowania na to konto. Spróbuj ponownie za 15 minut albo zresetuj hasło.');
}
if (hl_failed_attempts($conn, 'ip', $ip, ['password']) >= 20) {
    record_login_attempt($conn, null, $login, false, 'password', 'ip_locked');
    hl_login_fail('Zbyt wiele nieudanych prób logowania z tego adresu. Spróbuj ponownie później.');
}

$user = hl_auth_user($conn, 'login', $login);

if (!$user || !verifyPassword($password, (string)$user['pswd'])) {
    record_login_attempt($conn, $user ? (int)$user['id'] : null, $login, false, 'password', 'invalid_credentials');
    hl_login_fail('Nieprawidłowy login lub hasło.');
}

$userId = (int)$user['id'];

// stare hashe sha256 -> bcrypt przy okazji udanego logowania
if (needsRehash((string)$user['pswd'])) {
    $newHash = hashPassword($password);
    $stmt = $conn->prepare('UPDATE users SET pswd = ? WHERE id = ?');
    $stmt->bind_param('si', $newHash, $userId);
    $stmt->execute();
    $stmt->close();
    $user['pswd'] = $newHash;
}

// konto czeka na aktywację kodem z maila
if ((int)$user['status_id'] === 2) {
    $stmt = $conn->prepare('SELECT id FROM activation_tokens WHERE user_id = ? AND used = 0 LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $pendingActivation = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($pendingActivation) {
        record_login_attempt($conn, $userId, $login, false, 'password', 'account_inactive');
        hl_login_fail('Twoje konto nie zostało jeszcze aktywowane. Wpisz kod z maila.', 'warning', '../../activate.php?email=' . urlencode($user['login']));
    }
}

// hasło jest poprawne, więc komunikat o blokadzie niczego nie zdradza osobie postronnej
if ((int)$user['can_login'] !== 1) {
    record_login_attempt($conn, $userId, $login, false, 'password', 'account_blocked');
    hl_login_fail('Konto jest nieaktywne, zablokowane lub wyłączone. Skontaktuj się z administratorem.', 'warning');
}

if (hl_user_has_2fa($conn, $userId)) {
    if (!headers_sent()) header_remove('Set-Cookie');
    session_regenerate_id(true);
    $_SESSION['pending_2fa'] = [
        'user_id' => $userId,
        'login' => $user['login'],
        'remember' => $remember ? 1 : 0,
        'attempts' => 0,
        'created_at' => time(),
    ];
    hl_login_fail('Podaj kod z aplikacji uwierzytelniającej lub kod zapasowy.', 'info');
}

hl_login_user($conn, $user, 'password', $remember);
$_SESSION['alert'] = 'Zalogowano pomyślnie.';
$_SESSION['alert_type'] = 'success';
header('Location: ../../panel.php');
