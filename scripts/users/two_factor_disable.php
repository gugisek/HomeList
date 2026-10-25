<?php
// Wyłączenie 2FA – wymaga hasła i kodu (TOTP albo zapasowego) – JSON
header('Content-Type: application/json');
include '../security_scripts.php';
include '../conn_db.php';
require_once '../two_factor.php';
require_once '../password_helper.php';
require_once '../access.php';
require_once '../log_lib.php';

$userId = (int)$_SESSION['login_id'];
$password = (string)($_POST['password'] ?? '');
$code = twoFactorNormalizeBackupCode((string)($_POST['code'] ?? ''));
if ($password === '' || $code === '') {
    hl_json_exit('warning', 'Podaj hasło oraz kod 2FA lub kod zapasowy.');
}
if (hl_failed_attempts($conn, 'user', $userId, ['2fa', 'backup_code']) >= 5) {
    hl_json_exit('error', 'Za dużo nieudanych prób. Spróbuj ponownie za 15 minut.');
}

$stmt = $conn->prepare('SELECT pswd FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
if (!verifyPassword($password, (string)($stmt->get_result()->fetch_assoc()['pswd'] ?? ''))) {
    hl_json_exit('error', 'Nieprawidłowe hasło.');
}

$stmt = $conn->prepare('SELECT secret, is_enabled FROM user_two_factor_settings WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();
if (!$settings || (int)$settings['is_enabled'] !== 1) {
    hl_json_exit('warning', '2FA nie jest aktywne.');
}

$valid = twoFactorVerifyCode(twoFactorDecryptSecret((string)$settings['secret']) ?? '', $code, 1);
if (!$valid) {
    $stmt = $conn->prepare('SELECT id, code_hash FROM user_two_factor_backup_codes WHERE user_id = ? AND used_at IS NULL');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (password_verify($code, $row['code_hash'])) {
            $valid = true;
            break;
        }
    }
}
if (!$valid) {
    record_login_attempt($conn, $userId, (string)$_SESSION['login'], false, '2fa', '2fa_invalid');
    hl_json_exit('error', 'Nieprawidłowy kod 2FA / zapasowy.');
}

$stmt = $conn->prepare('UPDATE user_two_factor_settings SET is_enabled = 0, secret = NULL, enabled_at = NULL WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt = $conn->prepare('DELETE FROM user_two_factor_backup_codes WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();

hl_log($conn, $userId, 'users', '2FA aktywne', '2FA wyłączone', 1, 'Wyłączono uwierzytelnianie dwuskładnikowe (2FA)');
hl_json_exit('success', '2FA zostało wyłączone.');
