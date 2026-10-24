<?php
// Zapis / usunięcie subskrypcji push urządzenia (POST endpoint, action=subscribe|unsubscribe)
header('Content-Type: application/json');
include '../security_scripts.php';
include '../conn_db.php';
include '../planner/planner_lib.php';

$user_id = (int)$_SESSION['login_id'];
$endpoint = trim($_POST['endpoint'] ?? '');
if (!preg_match('~^https://~', $endpoint) || strlen($endpoint) > 700) {
    hl_json('error', 'Nieprawidłowa subskrypcja');
    exit();
}
$hash = sha1($endpoint);
if (($_POST['action'] ?? 'subscribe') === 'unsubscribe') {
    mysqli_query($conn, "DELETE FROM push_subscriptions WHERE endpoint_hash='$hash' AND user_id='$user_id'");
    hl_json('success', 'Powiadomienia wyłączone na tym urządzeniu');
    exit();
}
$e = mysqli_real_escape_string($conn, $endpoint);
$ua = mysqli_real_escape_string($conn, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255));
// ten sam endpoint mógł należeć do innego konta na tym urządzeniu – przepinamy na zalogowanego
mysqli_query($conn, "INSERT INTO push_subscriptions (user_id, endpoint, endpoint_hash, user_agent) VALUES ('$user_id', '$e', '$hash', '$ua')
                     ON DUPLICATE KEY UPDATE user_id='$user_id', user_agent='$ua'");
mysqli_query($conn, "INSERT IGNORE INTO push_settings (user_id) VALUES ('$user_id')");
hl_json('success', 'Powiadomienia włączone');
