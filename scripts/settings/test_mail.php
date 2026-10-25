<?php
// Testowy mail do zalogowanego administratora (sprawdzenie konfiguracji SMTP) – JSON
header('Content-Type: application/json');
$SECURITY_REQUIRED_MODULE = ['settings', 'edit'];
include '../security_scripts.php';
include '../conn_db.php';
require_once '../mailer.php';
require_once '../access.php';

$stmt = $conn->prepare('SELECT mail, login FROM users WHERE id = ?');
$user_id = (int)$_SESSION['login_id'];
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$to = filter_var($user['mail'] ?? '', FILTER_VALIDATE_EMAIL) ? $user['mail'] : ($user['login'] ?? '');

$result = send_app_mail([
    'to' => $to,
    'subject' => 'Test wysyłki HomeList',
    'body' => '<p>Konfiguracja SMTP działa – ta wiadomość dotarła z panelu HomeList.</p>',
]);
hl_json_exit($result['status'], $result['status'] === 'success' ? 'Wysłano testowy mail na ' . $to : $result['message']);
