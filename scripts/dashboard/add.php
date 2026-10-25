<?php
header('Content-Type: application/json'); // Nagłówek JSON

$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include "../conn_db.php";
include "../planner/planner_lib.php";
include "../access.php";

$user_id = (int)$_SESSION['login_id'];
$name = trim((string)($_POST['name'] ?? ''));
$description = (string)($_POST['description'] ?? '');
$deadline = hl_parse_deadline($_POST['deadline'] ?? '');
$list_id = (int)($_POST['list'] ?? 0);
$link_url = hl_parse_link($_POST['link_url'] ?? '');

if ($name === '' || !$list_id) {
    hl_json_exit('warning', 'Wypełnij wszystkie pola');
}

$access = hl_list_access($conn, $user_id, $list_id);
if (!$access) {
    hl_json_exit('error', 'Nie masz dostępu do tej listy');
}

$name = hl_substr($name, 500);
$description = hl_substr($description, 1000);
$create_date = date('Y-m-d H:i:s');
$cols = ['title' => $name, 'description' => $description, 'deadline_date' => $deadline, 'list_id' => $list_id, 'status_id' => 1,
         'creator_id' => $user_id, 'create_date' => $create_date, 'link_url' => $link_url];

// lista rutyn: dodatkowe pola powtarzania, bez deadline'u
if ($access['list']['list_type'] === 'routine') {
    $routine = hl_routine_fields_from_post();
    if (is_string($routine)) {
        hl_json_exit('warning', $routine);
    }
    $cols['deadline_date'] = null;
    $cols = array_merge($cols, hl_routine_bind_values($routine));
    $cols['recurrence_start'] = $routine['_start'] ?? date('Y-m-d');
}

$sql = 'INSERT INTO list_elements (' . implode(', ', array_keys($cols)) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
$stmt = $conn->prepare($sql);
$values = array_values($cols);
$stmt->bind_param(str_repeat('s', count($values)), ...$values);
if ($stmt->execute()) {
    $id = $stmt->insert_id;

    //log
    $before = '';
    $after = 'Nazwa: '.$name.' <br/>Opis: '.$description.' <br/>Deadline: '.($deadline ?: 'brak').' <br/>Lista: '.$list_id;
    $object_id = $id;
    $object_type="ListElement";
    $action_type = '2';
    $desc = 'Dodano zadanie do listy';
    include "../log_stripped_without_conn.php";
    //log
    hl_json_exit('success', 'Dodano zadanie');
}
hl_json_exit('error', 'Błąd dodawania zadania');
