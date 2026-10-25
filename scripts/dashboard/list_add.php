<?php
header('Content-Type: application/json'); // Nagłówek JSON

$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include "../conn_db.php";
include "../planner/planner_lib.php";
include "../access.php";
require_once "../login/account_lib.php";

$name = hl_substr(trim((string)($_POST['name'] ?? '')), 100);
$list_type = (isset($_POST['type']) && $_POST['type'] === 'routine') ? 'routine' : 'normal';

if ($name === '') {
    hl_json_exit('warning', 'Wypełnij wszystkie pola');
}

$owner_id = (int)$_SESSION['login_id'];
$list_code = hl_new_list_code($conn);

$stmt = $conn->prepare('INSERT INTO lists (name, full_name, owner_id, list_code, list_type) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('ssiss', $name, $name, $owner_id, $list_code, $list_type);
if ($stmt->execute()) {
    $id = $stmt->insert_id;

    //log
    $before = '';
    $after = 'Nazwa: '.$name.' <br/>Owner: '.$owner_id.' <br/>Kod: '.$list_code.' <br/>Typ: '.$list_type;
    $object_id = $id;
    $object_type="List";
    $action_type = '2';
    $desc = 'Dodano listę';
    include "../log_stripped_without_conn.php";
    //log
    hl_json_exit('success', 'Dodano listę', ['id' => $id]);
}
hl_json_exit('error', 'Błąd dodawania listy');
