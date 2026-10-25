<?php
// 2FA krok 1: nowy sekret TOTP (trzymany w sesji do potwierdzenia kodem) – JSON
header('Content-Type: application/json');
include '../security_scripts.php';
include '../conn_db.php';
require_once '../two_factor.php';
require_once '../access.php';

$userId = (int)$_SESSION['login_id'];
if (hl_user_has_2fa($conn, $userId)) {
    hl_json_exit('warning', '2FA jest już aktywne.');
}

$secret = twoFactorGenerateSecret(32);
$_SESSION['two_factor_setup_secret'] = $secret;
$_SESSION['two_factor_setup_time'] = time();

hl_json_exit('success', 'Dodaj konto w aplikacji uwierzytelniającej i wpisz kod.', [
    'secret' => $secret,
    'otp_auth_uri' => twoFactorBuildOtpAuthUri($secret, (string)$_SESSION['login'], 'HomeList'),
]);
