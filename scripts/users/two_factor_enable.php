<?php
// 2FA krok 2: potwierdzenie kodem z aplikacji -> włączenie + kody zapasowe – JSON
header('Content-Type: application/json');
include '../security_scripts.php';
include '../conn_db.php';
require_once '../two_factor.php';
require_once '../access.php';
require_once '../log_lib.php';

$userId = (int)$_SESSION['login_id'];
$code = trim((string)($_POST['code'] ?? ''));
$secret = $_SESSION['two_factor_setup_secret'] ?? '';

if ($secret === '' || time() - (int)($_SESSION['two_factor_setup_time'] ?? 0) > 900) {
    unset($_SESSION['two_factor_setup_secret'], $_SESSION['two_factor_setup_time']);
    hl_json_exit('warning', 'Konfiguracja wygasła. Zacznij od nowa.');
}
if (!twoFactorVerifyCode($secret, $code, 1)) {
    hl_json_exit('error', 'Nieprawidłowy kod z aplikacji. Sprawdź godzinę w telefonie i spróbuj ponownie.');
}

$backup = twoFactorGenerateBackupCodes(8);
$encrypted = twoFactorEncryptSecret($secret);

$conn->begin_transaction();
try {
    $stmt = $conn->prepare('INSERT INTO user_two_factor_settings (user_id, secret, is_enabled, enabled_at, created_at) VALUES (?, ?, 1, NOW(), NOW())
                            ON DUPLICATE KEY UPDATE secret = VALUES(secret), is_enabled = 1, enabled_at = NOW()');
    $stmt->bind_param('is', $userId, $encrypted);
    $stmt->execute();
    $stmt = $conn->prepare('DELETE FROM user_two_factor_backup_codes WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt = $conn->prepare('INSERT INTO user_two_factor_backup_codes (user_id, code_hash, created_at) VALUES (?, ?, NOW())');
    foreach ($backup['hashed'] as $hash) {
        $stmt->bind_param('is', $userId, $hash);
        $stmt->execute();
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    hl_json_exit('error', 'Nie udało się włączyć 2FA.');
}

unset($_SESSION['two_factor_setup_secret'], $_SESSION['two_factor_setup_time']);
hl_log($conn, $userId, 'users', '', 'Włączono 2FA', 1, 'Włączono uwierzytelnianie dwuskładnikowe (2FA)');
hl_json_exit('success', '2FA zostało włączone. Zapisz kody zapasowe.', ['backup_codes' => $backup['plain']]);
