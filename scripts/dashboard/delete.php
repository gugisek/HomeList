<?php
header('Content-Type: application/json'); // Nagłówek JSON

$SECURITY_REQUIRED_MODULE = ['dashboard', 'full'];
include '../security_scripts.php';
include "../conn_db.php";
include "../access.php";

$id = (int)($_POST['id'] ?? 0);
if (!$id || ($_POST['delete'] ?? '') !== 'true') {
    hl_json_exit('warning', 'Wypełnij wszystkie pola');
}

$row_old = hl_element_access($conn, (int)$_SESSION['login_id'], $id);
if (!$row_old) {
    hl_json_exit('error', 'Nie znaleziono zadania');
}

$stmt = $conn->prepare('DELETE FROM list_elements WHERE id = ?');
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    // plan dnia i odhaczenia rutyn usuniętego elementu
    foreach (['DELETE FROM day_plan WHERE element_id = ?', 'DELETE FROM routine_done WHERE element_id = ?'] as $sql) {
        $cleanup = $conn->prepare($sql);
        $cleanup->bind_param('i', $id);
        $cleanup->execute();
    }

    //log
    $before = 'Nazwa: '.$row_old['title'].' <br/>Opis: '.$row_old['description'].' <br/>Deadline: '.$row_old['deadline_date'].' <br/>Lista: '.$row_old['list_id'];
    $after = '';
    $object_id = $id;
    $object_type="ListElement";
    $action_type = '3';
    $desc = 'Usunięto zadanie z listy';
    include "../log_stripped_without_conn.php";
    //log
    hl_json_exit('success', 'Usunięto zadanie');
}
hl_json_exit('error', 'Błąd usuwania zadania');
