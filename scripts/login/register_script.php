<?php
// Rejestracja (AJAX z components/login/register_form.php) – JSON {status, message, redirect?}.
// Nowe konto dostaje rolę z ustawień (registration_role) i – jeśli włączona aktywacja i skonfigurowany
// SMTP – status "nieaktywne" do czasu wpisania kodu z maila.
header('Content-Type: application/json');
require_once __DIR__ . '/../session_bootstrap.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../password_helper.php';
require_once __DIR__ . '/../login_attempts.php';
include __DIR__ . '/../conn_db.php';
require_once __DIR__ . '/account_lib.php';

function hl_reg_json(string $status, string $message, array $extra = []): void
{
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify()) {
    hl_reg_json('warning', 'Formularz wygasł. Odśwież stronę i spróbuj ponownie.');
}
if (hl_setting($conn, 'registration_open', '1') !== '1') {
    hl_reg_json('warning', 'Rejestracja nowych kont jest obecnie wyłączona.');
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$rePassword = (string)($_POST['re_password'] ?? '');

if ($name === '' || $email === '' || $password === '' || $rePassword === '') {
    hl_reg_json('warning', 'Wszystkie pola muszą być wypełnione.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    hl_reg_json('warning', 'Nieprawidłowy adres email.');
}
if ((function_exists('mb_strlen') ? mb_strlen($name) : strlen($name)) > 60) {
    hl_reg_json('warning', 'Nazwa konta może mieć maksymalnie 60 znaków.');
}
if ($password !== $rePassword) {
    hl_reg_json('warning', 'Hasła nie są identyczne.');
}
if (($policy = passwordPolicyViolation($password)) !== null) {
    hl_reg_json('warning', $policy);
}

// prosty limit zakładania kont z jednego IP (10 na godzinę)
$ip = hl_client_ip();
if ($ip !== null) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM login_attempts WHERE ip_address = ? AND failure_reason = 'registered' AND created_at > NOW() - INTERVAL 1 HOUR");
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    if ((int)$stmt->get_result()->fetch_assoc()['cnt'] >= 10) {
        hl_reg_json('warning', 'Zbyt wiele rejestracji z tego adresu. Spróbuj ponownie później.');
    }
    $stmt->close();
}

$stmt = $conn->prepare('SELECT id, status_id FROM users WHERE LOWER(login) = LOWER(?) LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    if ((int)$existing['status_id'] === 2) {
        hl_reg_json('info', 'Konto już istnieje, ale nie zostało aktywowane. Przechodzę do aktywacji...', ['redirect' => 'activate?email=' . urlencode($email)]);
    }
    hl_reg_json('error', 'Konto z tym adresem email już istnieje. Zaloguj się albo zresetuj hasło.');
}

$needsActivation = hl_setting($conn, 'registration_activation', '1') === '1' && hl_mail_configured($conn);
$roleId = (int)hl_setting($conn, 'registration_role', '3');
$stmt = $conn->prepare('SELECT id FROM user_roles WHERE id = ?');
$stmt->bind_param('i', $roleId);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) $roleId = 3;
$stmt->close();

$statusId = $needsActivation ? 2 : 1;
$hash = hashPassword($password);
$stmt = $conn->prepare("INSERT INTO users (login, pswd, role_id, status_id, create_date, name, sur_name, mail) VALUES (?, ?, ?, ?, NOW(), ?, '', ?)");
$stmt->bind_param('ssiiss', $email, $hash, $roleId, $statusId, $name, $email);
if (!$stmt->execute()) {
    hl_reg_json('error', 'Błąd podczas rejestracji. Spróbuj ponownie później.');
}
$userId = (int)$stmt->insert_id;
$stmt->close();

// success = 1, żeby wpis nie liczył się do limitu nieudanych prób aktywacji (służy tylko limitowi per IP)
record_login_attempt($conn, $userId, $email, true, 'activation', 'registered');
hl_create_default_list($conn, $userId);
hl_log($conn, $userId, 'users', '', 'Email: ' . $email . '<br/>Rola: ' . $roleId, 2, $needsActivation ? 'Zarejestrowano konto (nieaktywne)' : 'Zarejestrowano konto', $userId);

if (!$needsActivation) {
    $_SESSION['alert'] = 'Rejestracja przebiegła pomyślnie. Możesz się teraz zalogować.';
    $_SESSION['alert_type'] = 'success';
    hl_reg_json('success', 'Rejestracja przebiegła pomyślnie.', ['redirect' => 'login']);
}

$mail = hl_send_activation_code($conn, $userId, $email);
if ($mail['status'] !== 'success') {
    error_log('register mail error: ' . $mail['message']);
    hl_reg_json('warning', 'Konto utworzone, ale nie udało się wysłać maila z kodem. Spróbuj „Wyślij kod ponownie”.', ['redirect' => 'activate?email=' . urlencode($email)]);
}
hl_reg_json('success', 'Konto utworzone! Sprawdź skrzynkę i wpisz kod aktywacyjny.', ['redirect' => 'activate?email=' . urlencode($email)]);
