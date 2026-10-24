<?php
// Włączenie / wyłączenie przypomnień o konkretnej rutynie (POST element_id, mute=0|1)
header('Content-Type: application/json');
include '../security_scripts.php';
include '../conn_db.php';
include '../planner/planner_lib.php';

$user_id = (int)$_SESSION['login_id'];
$element_id = (int)($_POST['element_id'] ?? 0);
if (!hl_get_element($conn, $user_id, $element_id)) {
    hl_json('error', 'Nie znaleziono rutyny');
    exit();
}
if (!empty($_POST['mute'])) {
    mysqli_query($conn, "INSERT IGNORE INTO push_mute (user_id, element_id) VALUES ('$user_id', '$element_id')");
    hl_json('success', 'Nie będziemy przypominać o tej rutynie');
} else {
    mysqli_query($conn, "DELETE FROM push_mute WHERE user_id='$user_id' AND element_id='$element_id'");
    hl_json('success', 'Przypomnienia o tej rutynie włączone');
}
