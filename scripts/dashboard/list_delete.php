<?php
// Właściciel: usuwa listę (owner_id = 0, zadania zostają w bazie). Zaproszony: odłącza listę od swojego konta.
header('Content-Type: application/json'); // Nagłówek JSON

$SECURITY_REQUIRED_MODULE = ['dashboard', 'edit'];
include '../security_scripts.php';
include "../conn_db.php";
include "../access.php";

$user_id = (int)$_SESSION['login_id'];
$id = (int)($_POST['id'] ?? 0);

if (!$id || ($_POST['delete_confirm'] ?? '') !== 'true') {
    hl_json_exit('warning', 'Wypełnij wszystkie pola');
}

$access = hl_list_access($conn, $user_id, $id);
if (!$access) {
    hl_json_exit('error', 'Nie znaleziono listy');
}
$row_old = $access['list'];

if ($access['role'] === 'member') {
    $stmt = $conn->prepare('DELETE FROM list_invities WHERE list_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $user_id);
    if ($stmt->execute()) {
        $cleanup = $conn->prepare('DELETE day_plan FROM day_plan JOIN list_elements ON list_elements.id = day_plan.element_id WHERE day_plan.user_id = ? AND list_elements.list_id = ?');
        $cleanup->bind_param('ii', $user_id, $id);
        $cleanup->execute();

        //log
        $before = 'Lista: '.$row_old['name'].' <br/>Użytkownik: '.$user_id;
        $after = '';
        $object_id = $id;
        $object_type="List";
        $action_type = '3';
        $desc = 'Odłączono listę od konta';
        include "../log_stripped_without_conn.php";
        //log
        hl_json_exit('success', 'Odłączono listę od Twojego konta');
    }
    hl_json_exit('error', 'Błąd odłączania listy');
}

hl_require('dashboard', 'full');
$stmt = $conn->prepare("UPDATE lists SET owner_id = '0' WHERE id = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    //log
    $before = 'Nazwa: '.$row_old['name'].' <br/>Owner: '.$row_old['owner_id'].' <br/>Kod: '.$row_old['list_code'];
    $after = '';
    $object_id = $id;
    $object_type="List";
    $action_type = '3';
    $desc = 'Usunięto listę';
    include "../log_stripped_without_conn.php";
    //log
    hl_json_exit('success', 'Usunięto listę pomyślnie');
}
hl_json_exit('error', 'Błąd usuwania listy');
