<?php
header('Content-Type: application/json'); // Nagłówek JSON

$SECURITY_REQUIRED_MODULE = ['dashboard', 'edit'];
include '../security_scripts.php';
include "../conn_db.php";
include "../planner/planner_lib.php";
include "../access.php";

$user_id = (int)$_SESSION['login_id'];
$element_id = (int)($_POST['element_id'] ?? 0);
$name = trim((string)($_POST['name'] ?? ''));
$description = hl_substr((string)($_POST['description'] ?? ''), 1000);
$deadline = hl_parse_deadline($_POST['deadline'] ?? '');
$link_url = hl_parse_link($_POST['link_url'] ?? '');

if ($name === '' || !$element_id) {
    hl_json_exit('warning', 'Wypełnij wszystkie pola');
}
$name = hl_substr($name, 500);

$row_old = hl_element_access($conn, $user_id, $element_id);
if (!$row_old) {
    hl_json_exit('error', 'Nie znaleziono zadania');
}

$cols = ['title' => $name, 'description' => $description, 'deadline_date' => $deadline, 'link_url' => $link_url];
$routine_changed = false;

// rutyna: pola powtarzania (porównanie z poprzednimi wartościami, żeby wykryć zmiany)
if ($row_old['list_type'] === 'routine') {
    $routine = hl_routine_fields_from_post();
    if (is_string($routine)) {
        hl_json_exit('warning', $routine);
    }
    $routine_cols = hl_routine_bind_values($routine);
    if ($routine['_start'] !== null) $routine_cols['recurrence_start'] = $routine['_start'];
    foreach ($routine_cols as $col => $val) {
        if ((string)$row_old[$col] !== (string)$val) $routine_changed = true;
    }
    $cols = array_merge($cols, $routine_cols);
    $cols['deadline_date'] = $row_old['deadline_date'];
}

if ($row_old['title'] === $name && (string)$row_old['description'] === $description && (string)$row_old['deadline_date'] === (string)$cols['deadline_date']
    && (string)$row_old['link_url'] === (string)$link_url && !$routine_changed) {
    hl_json_exit('warning', 'Wprowadzono te same dane');
}

$set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($cols)));
$values = array_values($cols);
$values[] = $element_id;
$stmt = $conn->prepare("UPDATE list_elements SET $set WHERE id = ?");
$stmt->bind_param(str_repeat('s', count($values) - 1) . 'i', ...$values);
if ($stmt->execute()) {
    //log
    $before = 'Nazwa: '.$row_old['title'].' <br/>Opis: '.$row_old['description'].' <br/>Deadline: '.($row_old['deadline_date'] ?: 'brak');
    $after = 'Nazwa: '.$name.' <br/>Opis: '.$description.' <br/>Deadline: '.($cols['deadline_date'] ?: 'brak');
    $object_id = $element_id;
    $object_type="ListElement";
    $action_type = '1';
    $desc = 'Zmieniono dane zadania';
    include "../log_stripped_without_conn.php";
    //log
    hl_json_exit('success', 'Zmieniono dane zadania');
}
hl_json_exit('error', 'Błąd edycji zadania');
