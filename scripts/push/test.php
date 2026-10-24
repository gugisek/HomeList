<?php
// Testowe powiadomienie na wszystkie urządzenia użytkownika
header('Content-Type: application/json');
include '../security_scripts.php';
include '../conn_db.php';
include '../planner/planner_lib.php';
include 'push_lib.php';

$user_id = (int)$_SESSION['login_id'];
$n = hl_push_notify($conn, $user_id, 'HomeList · test', 'Powiadomienia działają! Tak będą wyglądać przypomnienia.', 'test');
if ($n) {
    hl_json('success', "Wysłano na urządzenia: $n");
} else {
    hl_json('warning', 'Brak aktywnych urządzeń – zezwól na powiadomienia na tym urządzeniu');
}
