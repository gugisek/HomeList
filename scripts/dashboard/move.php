<?php
header('Content-Type: application/json'); // Nagłówek JSON

$SECURITY_REQUIRED_MODULE = ['dashboard', 'edit'];
include '../security_scripts.php';
include "../conn_db.php";
include "../access.php";

$user_id = (int)$_SESSION['login_id'];
$element_id = (int)($_POST['element_id'] ?? 0);
$list_id = (int)($_POST['list_id'] ?? 0);

if (!$list_id || !$element_id) {
    hl_json_exit('warning', 'Wypełnij wszystkie pola');
}

$row_old = hl_element_access($conn, $user_id, $element_id);
$target = hl_list_access($conn, $user_id, $list_id);
if (!$row_old || !$target) {
    hl_json_exit('error', 'Nie znaleziono zadania lub listy');
}
if ((int)$row_old['list_id'] === $list_id) {
    hl_json_exit('warning', 'wybrano tę samą listę');
}
// zadanie i rutyna mają różne pola – przenosimy tylko między listami tego samego typu
if ($row_old['list_type'] !== $target['list']['list_type']) {
    hl_json_exit('warning', 'Można przenosić tylko między listami tego samego typu');
}

$stmt = $conn->prepare('UPDATE list_elements SET list_id = ? WHERE id = ?');
$stmt->bind_param('ii', $list_id, $element_id);
if ($stmt->execute()) {
    //log
    $before = 'List ID: '.$row_old['list_id'];
    $after = 'List ID: '.$list_id;
    $object_id = $element_id;
    $object_type="ListElement";
    $action_type = '1';
    $desc = 'Zmieniono listę zadania';
    include "../log_stripped_without_conn.php";
    //log
    hl_json_exit('success', 'Zmieniono listę zadania');
}
hl_json_exit('error', 'Błąd zmiany listy zadania');
