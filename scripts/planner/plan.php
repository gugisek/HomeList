<?php
// Planowanie zwykłego zadania na dzień + porę dnia (lub usunięcie z planu)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
include 'planner_lib.php';

$user_id = (int)$_SESSION['login_id'];
$element_id = (int)($_POST['element_id'] ?? 0);
$action = $_POST['action'] ?? 'plan';

$el = hl_get_element($conn, $user_id, $element_id);
if (!$el || $el['list_type'] !== 'normal') {
    hl_json('error', 'Nie znaleziono zadania');
    exit;
}

if ($action === 'unplan') {
    if (mysqli_query($conn, "DELETE FROM day_plan WHERE user_id = '$user_id' AND element_id = '$element_id'")) {
        hl_json('success', 'Usunięto z planu');
    } else {
        hl_json('error', 'Błąd usuwania z planu');
    }
    exit;
}

$date = $_POST['date'] ?? '';
$part = (int)($_POST['day_part'] ?? 0);
$time = $_POST['plan_time'] ?? '';
if (!hl_valid_date($date) || !array_key_exists($part, hl_parts())) {
    hl_json('warning', 'Wybierz dzień i porę dnia');
    exit;
}
$time_sql = preg_match('/^\d{2}:\d{2}$/', $time) ? "'$time:00'" : "NULL";
$d = mysqli_real_escape_string($conn, $date);

// zadanie ma w planie użytkownika maksymalnie jeden termin
mysqli_query($conn, "DELETE FROM day_plan WHERE user_id = '$user_id' AND element_id = '$element_id'");
$position = hl_next_position($conn, $user_id, $date, $part);
$sql = "INSERT INTO day_plan (user_id, element_id, plan_date, day_part, plan_time, position)
        VALUES ('$user_id', '$element_id', '$d', '$part', $time_sql, '$position')";
if (mysqli_query($conn, $sql)) {
    hl_json('success', 'Zaplanowano: '.hl_plan_label($date, $part, date('Y-m-d')));
} else {
    hl_json('error', 'Błąd planowania zadania');
}
