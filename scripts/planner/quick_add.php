<?php
// Szybkie dodanie zadania z feedu: tworzy zadanie na wybranej liście i od razu planuje je na dzień + porę
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

include '../security_scripts.php';
include '../conn_db.php';
include 'planner_lib.php';

$user_id = (int)$_SESSION['login_id'];
$title = trim($_POST['title'] ?? '');
$list_id = (int)($_POST['list_id'] ?? 0);
$date = $_POST['date'] ?? '';
$part = (int)($_POST['day_part'] ?? 0);
$time = $_POST['plan_time'] ?? '';

if ($title === '' || !$list_id) {
    hl_json('warning', 'Podaj nazwę zadania i wybierz listę');
    exit;
}
if (!hl_valid_date($date) || !array_key_exists($part, hl_parts())) {
    hl_json('warning', 'Wybierz dzień i porę dnia');
    exit;
}

$result = mysqli_query($conn, "SELECT list_type FROM lists WHERE id='$list_id' AND id IN (".hl_in(hl_user_list_ids($conn, $user_id)).")");
$list = mysqli_fetch_assoc($result);
if (!$list || $list['list_type'] !== 'normal') {
    hl_json('error', 'Nieprawidłowa lista');
    exit;
}

$title_sql = mysqli_real_escape_string($conn, hl_substr($title, 500));
$create_date = date('Y-m-d H:i:s');
$sql = "INSERT INTO list_elements (title, description, deadline_date, list_id, status_id, creator_id, create_date, link_url)
        VALUES ('$title_sql', '', NULL, '$list_id', '1', '$user_id', '$create_date', NULL)";
if (!mysqli_query($conn, $sql)) {
    hl_json('error', 'Błąd dodawania zadania');
    exit;
}
$element_id = mysqli_insert_id($conn);

//log
$before = '';
$after = 'Nazwa: '.$title_sql.' <br/>Lista: '.$list_id.' <br/>Plan: '.$date;
$object_id = $element_id;
$object_type = "ListElement";
$action_type = '2';
$desc = 'Dodano zadanie z planu dnia';
include "../log_stripped_without_conn.php";
//log

$d = mysqli_real_escape_string($conn, $date);
$time_sql = preg_match('/^\d{2}:\d{2}$/', $time) ? "'$time:00'" : "NULL";
$position = hl_next_position($conn, $user_id, $date, $part);
mysqli_query($conn, "INSERT INTO day_plan (user_id, element_id, plan_date, day_part, plan_time, position)
                     VALUES ('$user_id', '$element_id', '$d', '$part', $time_sql, '$position')");

hl_json('success', 'Dodano zadanie do planu');
