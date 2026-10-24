<?php
// Zapis ustawień powiadomień
header('Content-Type: application/json');
include '../security_scripts.php';
include '../conn_db.php';
include '../planner/planner_lib.php';
include 'push_lib.php';

$user_id = (int)$_SESSION['login_id'];
$set = [];
foreach (hl_push_defaults() as $k => $v) {
    if (is_int($v)) {
        $set[$k] = !empty($_POST[$k]) ? 1 : 0;
    } else {
        $t = $_POST[$k] ?? '';
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t)) {
            hl_json('error', 'Nieprawidłowa godzina');
            exit();
        }
        $set[$k] = $t;
    }
}
if (!($set['morning_start'] < $set['afternoon_start'] && $set['afternoon_start'] < $set['evening_start'] && $set['evening_start'] < $set['day_end'])) {
    hl_json('warning', 'Pory dnia muszą następować po sobie: rano < popołudnie < wieczór < koniec dnia');
    exit();
}
$cols = implode(', ', array_keys($set));
$vals = implode(', ', array_map(fn($v) => "'$v'", $set));
$upd = implode(', ', array_map(fn($k) => "$k=VALUES($k)", array_keys($set)));
mysqli_query($conn, "INSERT INTO push_settings (user_id, $cols) VALUES ('$user_id', $vals) ON DUPLICATE KEY UPDATE $upd");
hl_json('success', 'Zapisano ustawienia powiadomień');
