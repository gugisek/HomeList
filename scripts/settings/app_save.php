<?php
// Zapis ustawień aplikacji (Ustawienia > Rejestracja / Email) – JSON
header('Content-Type: application/json');
$SECURITY_REQUIRED_MODULE = ['settings', 'edit'];
include '../security_scripts.php';
include '../conn_db.php';
require_once '../app_settings.php';
require_once '../access.php';
require_once '../log_lib.php';

$section = $_POST['section'] ?? '';
$values = [];

if ($section === 'registration') {
    require_once '../users/user_admin_lib.php';
    $role = (int)($_POST['registration_role'] ?? 0);
    $role_row = hl_role_row($conn, $role);
    if (!$role_row) {
        hl_json_exit('warning', 'Wybierz istniejącą rolę');
    }
    // inaczej dałoby się nadać sobie wyższe uprawnienia rejestrując nowe konto
    if (!hl_role_within_mine($role_row)) {
        hl_json_exit('error', 'Rola nowych kont nie może mieć wyższych uprawnień niż Twoje');
    }
    $values = [
        'registration_open' => !empty($_POST['registration_open']) ? '1' : '0',
        'registration_activation' => !empty($_POST['registration_activation']) ? '1' : '0',
        'registration_role' => (string)$role,
    ];
} elseif ($section === 'mail') {
    $email = trim((string)($_POST['smtp_email'] ?? ''));
    $server = trim((string)($_POST['smtp_server'] ?? ''));
    $port = (int)($_POST['smtp_port'] ?? 587);
    $secure = in_array($_POST['smtp_secure'] ?? '', ['tls', 'ssl', 'none'], true) ? $_POST['smtp_secure'] : 'tls';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        hl_json_exit('warning', 'Nieprawidłowy adres nadawcy');
    }
    if ($server !== '' && !preg_match('/^[a-z0-9.-]+$/i', $server)) {
        hl_json_exit('warning', 'Nieprawidłowy adres serwera SMTP');
    }
    if ($port < 1 || $port > 65535) {
        hl_json_exit('warning', 'Nieprawidłowy port');
    }
    $values = [
        'smtp_server' => $server,
        'smtp_port' => (string)$port,
        'smtp_secure' => $secure,
        'smtp_email' => $email,
        'smtp_name' => trim((string)($_POST['smtp_name'] ?? 'HomeList')),
        'smtp_username' => trim((string)($_POST['smtp_username'] ?? '')),
    ];
    // hasło nie wraca do przeglądarki – puste pole = bez zmian
    if ((string)($_POST['smtp_auth_token'] ?? '') !== '') $values['smtp_auth_token'] = (string)$_POST['smtp_auth_token'];
} else {
    hl_json_exit('warning', 'Nieznana sekcja ustawień');
}

foreach ($values as $name => $value) hl_setting_save($conn, $name, $value);

$logged = $values;
if (isset($logged['smtp_auth_token'])) $logged['smtp_auth_token'] = '(zmienione)';
hl_log($conn, 0, 'settings', '', implode(' <br/>', array_map(fn($k, $v) => $k . ': ' . $v, array_keys($logged), $logged)), 1, 'Zmieniono ustawienia aplikacji (' . $section . ')');
hl_json_exit('success', 'Zapisano ustawienia');
