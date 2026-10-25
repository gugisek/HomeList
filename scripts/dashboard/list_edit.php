<?php
header('Content-Type: application/json'); // Nagłówek JSON

$SECURITY_REQUIRED_MODULE = ['dashboard', 'edit'];
include '../security_scripts.php';
include "../conn_db.php";
include "../planner/planner_lib.php";
include "../access.php";

$list_id = (int)($_POST['id'] ?? 0);
$name = hl_substr(trim((string)($_POST['name'] ?? '')), 100);

if ($name === '' || !$list_id) {
    hl_json_exit('warning', 'Wypełnij wszystkie pola');
}

$access = hl_list_access($conn, (int)$_SESSION['login_id'], $list_id);
if (!$access) {
    hl_json_exit('error', 'Nie znaleziono listy');
}
if ($access['role'] !== 'owner') {
    hl_json_exit('warning', 'Nazwę listy może zmienić tylko jej właściciel');
}
$row_old = $access['list'];
if ($row_old['full_name'] === $name) {
    hl_json_exit('warning', 'Wprowadzono te same dane');
}

$stmt = $conn->prepare('UPDATE lists SET name = ?, full_name = ? WHERE id = ?');
$stmt->bind_param('ssi', $name, $name, $list_id);
if ($stmt->execute()) {
    //log
    $before = 'Nazwa: '.$row_old['name'];
    $after = 'Nazwa: '.$name;
    $object_id = $list_id;
    $object_type="Lists";
    $action_type = '1';
    $desc = 'Zmieniono nazwę listy';
    include "../log_stripped_without_conn.php";
    //log
    hl_json_exit('success', 'Zmieniono nazwę listy');
}
hl_json_exit('error', 'Błąd edycji listy');
