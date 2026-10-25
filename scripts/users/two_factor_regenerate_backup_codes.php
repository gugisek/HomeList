<?php
// Nowe kody zapasowe 2FA (stare przestają działać) – wymaga aktualnego kodu z aplikacji – JSON
header('Content-Type: application/json');
include '../security_scripts.php';
include '../conn_db.php';
require_once '../two_factor.php';
require_once '../access.php';
require_once '../log_lib.php';

$userId = (int)$_SESSION['login_id'];
$code = trim((string)($_POST['code'] ?? ''));
if ($code === '') {
    hl_json_exit('warning', 'Podaj aktualny kod z aplikacji.');
}

$stmt = $conn->prepare('SELECT secret, is_enabled FROM user_two_factor_settings WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();
if (!$settings || (int)$settings['is_enabled'] !== 1) {
    hl_json_exit('warning', '2FA nie jest aktywne.');
}
if (!twoFactorVerifyCode(twoFactorDecryptSecret((string)$settings['secret']) ?? '', $code, 1)) {
    hl_json_exit('error', 'Nieprawidłowy kod z aplikacji.');
}

$backup = twoFactorGenerateBackupCodes(8);
$conn->begin_transaction();
$stmt = $conn->prepare('DELETE FROM user_two_factor_backup_codes WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt = $conn->prepare('INSERT INTO user_two_factor_backup_codes (user_id, code_hash, created_at) VALUES (?, ?, NOW())');
foreach ($backup['hashed'] as $hash) {
    $stmt->bind_param('is', $userId, $hash);
    $stmt->execute();
}
$conn->commit();

hl_log($conn, $userId, 'users', '', 'Nowe kody zapasowe 2FA', 1, 'Wygenerowano nowe kody zapasowe 2FA');
hl_json_exit('success', 'Wygenerowano nowe kody zapasowe.', ['backup_codes' => $backup['plain']]);
