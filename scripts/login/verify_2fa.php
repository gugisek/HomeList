<?php
// Drugi krok logowania: kod TOTP albo jednorazowy kod zapasowy (pending_2fa ustawia login_script.php).
require_once __DIR__ . '/../auth_lib.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../two_factor.php';
include __DIR__ . '/../conn_db.php';

function hl_2fa_back(string $message, string $type = 'error', bool $abort = false): void
{
    if ($abort) unset($_SESSION['pending_2fa']);
    $_SESSION['alert'] = $message;
    $_SESSION['alert_type'] = $type;
    header('Location: ../../login');
    exit();
}

$pending = $_SESSION['pending_2fa'] ?? null;
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$pending || empty($pending['user_id'])) {
    hl_2fa_back('Sesja logowania wygasła. Zaloguj się ponownie.', 'warning', true);
}
if (!csrf_verify()) {
    hl_2fa_back('Formularz wygasł. Spróbuj ponownie.', 'warning');
}
if (time() - (int)($pending['created_at'] ?? 0) > 600) {
    hl_2fa_back('Sesja logowania wygasła. Zaloguj się ponownie.', 'warning', true);
}

$userId = (int)$pending['user_id'];
$login = (string)($pending['login'] ?? '');
$code = twoFactorNormalizeBackupCode((string)($_POST['two_factor_code'] ?? ''));

if ($code === '') {
    hl_2fa_back('Wpisz kod 2FA lub kod zapasowy.', 'warning');
}
if ((int)($pending['attempts'] ?? 0) >= 5) {
    hl_2fa_back('Za dużo nieudanych prób 2FA. Zaloguj się ponownie.', 'error', true);
}
// licznik w sesji zeruje się przy nowym logowaniu hasłem, więc liczymy też per konto w bazie
if (hl_failed_attempts($conn, 'user', $userId, ['2fa', 'backup_code']) >= 5) {
    record_login_attempt($conn, $userId, $login, false, '2fa', '2fa_locked');
    hl_2fa_back('Za dużo nieudanych prób 2FA na tym koncie. Spróbuj ponownie za 15 minut.', 'error', true);
}

$stmt = $conn->prepare('SELECT secret, is_enabled FROM user_two_factor_settings WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();
$stmt->close();

$secret = ($settings && (int)$settings['is_enabled'] === 1 && !empty($settings['secret']))
    ? twoFactorDecryptSecret((string)$settings['secret']) : null;
if ($secret === null) {
    hl_2fa_back('2FA nie jest już aktywne albo klucz szyfrowania się zmienił. Zaloguj się ponownie.', 'warning', true);
}

$valid = twoFactorVerifyCode($secret, $code, 1);
$usedBackup = false;

if (!$valid) {
    $stmt = $conn->prepare('SELECT id, code_hash FROM user_two_factor_backup_codes WHERE user_id = ? AND used_at IS NULL');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (password_verify($code, $row['code_hash'])) {
            // warunek used_at IS NULL w samym UPDATE – dwa równoległe żądania z tym samym kodem nie przejdą oba
            $backupId = (int)$row['id'];
            $consume = $conn->prepare('UPDATE user_two_factor_backup_codes SET used_at = NOW() WHERE id = ? AND used_at IS NULL');
            $consume->bind_param('i', $backupId);
            $consume->execute();
            $valid = $usedBackup = $consume->affected_rows === 1;
            $consume->close();
            break;
        }
    }
    $stmt->close();
}

if (!$valid) {
    record_login_attempt($conn, $userId, $login, false, '2fa', '2fa_invalid');
    $_SESSION['pending_2fa']['attempts'] = (int)($pending['attempts'] ?? 0) + 1;
    hl_2fa_back('Nieprawidłowy kod 2FA / zapasowy.');
}

$user = hl_auth_user($conn, 'id', $userId);
if (!$user || (int)$user['can_login'] !== 1) {
    hl_2fa_back('Konto jest nieaktywne lub zablokowane.', 'warning', true);
}

$stmt = $conn->prepare('UPDATE user_two_factor_settings SET last_used_at = NOW() WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->close();

hl_login_user($conn, $user, $usedBackup ? 'backup_code' : '2fa', (int)($pending['remember'] ?? 0) === 1);
$_SESSION['alert'] = $usedBackup ? 'Zalogowano kodem zapasowym – został on zużyty.' : 'Zalogowano pomyślnie.';
$_SESSION['alert_type'] = 'success';
header('Location: ../../panel');
