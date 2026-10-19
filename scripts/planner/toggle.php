<?php
// Odhaczanie w feedzie: zwykłe zadanie (status Zrobione/Oczekuje) albo rutyna (na konkretny dzień)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

include '../security_scripts.php';
include '../conn_db.php';
include 'planner_lib.php';

$user_id = (int)$_SESSION['login_id'];
$type = $_POST['type'] ?? '';
$element_id = (int)($_POST['id'] ?? 0);
$done = ($_POST['done'] ?? '') === '1';
$today = date('Y-m-d');
$date = hl_valid_date($_POST['date'] ?? '') ? $_POST['date'] : $today;

$el = hl_get_element($conn, $user_id, $element_id);
if (!$el) {
    hl_json('error', 'Nie znaleziono zadania');
    exit;
}

if ($type === 'task' && $el['list_type'] === 'normal') {
    if ($done) {
        $sql = "UPDATE list_elements SET status_id = '2', done_date = NOW() WHERE id = '$element_id'";
    } else {
        $sql = "UPDATE list_elements SET status_id = '1' WHERE id = '$element_id'";
    }
    if (mysqli_query($conn, $sql)) {
        //log
        $before = 'Status: '.$el['status_id'];
        $after = 'Status: '.($done ? 2 : 1);
        $object_id = $element_id;
        $object_type = "ListElement";
        $action_type = '1';
        $desc = 'Zmieniono status zadania (plan dnia)';
        include "../log_stripped_without_conn.php";
        //log
        hl_json('success', $done ? 'Zrobione!' : 'Cofnięto odhaczenie');
    } else {
        hl_json('error', 'Błąd zmiany statusu zadania');
    }
    exit;
}

if ($type === 'routine' && $el['list_type'] === 'routine') {
    if ($date > $today) {
        hl_json('warning', 'Rutynę można odhaczyć dopiero w jej dniu');
        exit;
    }
    if ($done && !hl_routine_occurs($el, $date)) {
        hl_json('warning', 'Ta rutyna nie przypada na ten dzień');
        exit;
    }
    $d = mysqli_real_escape_string($conn, $date);
    $shared = (int)$el['shared_done'] === 1;
    if ($done) {
        $sql = "INSERT IGNORE INTO routine_done (element_id, user_id, done_date, done_at) VALUES ('$element_id', '$user_id', '$d', NOW())";
    } elseif ($shared) {
        // wspólne odhaczanie: cofnięcie zdejmuje odhaczenie wszystkim
        $sql = "DELETE FROM routine_done WHERE element_id = '$element_id' AND done_date = '$d'";
    } else {
        $sql = "DELETE FROM routine_done WHERE element_id = '$element_id' AND user_id = '$user_id' AND done_date = '$d'";
    }
    if (mysqli_query($conn, $sql)) {
        $done_map = hl_routine_done_map($conn, [$el], $user_id, hl_add_days($today, -400), $today);
        hl_json('success', $done ? 'Zrobione!' : 'Cofnięto odhaczenie', [
            'streak' => hl_streak($el, $done_map[$element_id], $today),
        ]);
    } else {
        hl_json('error', 'Błąd zapisu rutyny');
    }
    exit;
}

hl_json('warning', 'Nieprawidłowe dane');
