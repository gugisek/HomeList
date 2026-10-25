<?php
header('Content-Type: application/json'); // Nagłówek JSON

$SECURITY_REQUIRED_MODULE = ['dashboard', 'edit'];
include '../security_scripts.php';
include "../conn_db.php";
include "../access.php";

$element_id = (int)($_POST['element_id'] ?? 0);
$status_id = (int)($_POST['status_id'] ?? 0);

if (!$element_id || !in_array($status_id, [1, 2, 3, 4], true)) {
    hl_json_exit('warning', 'Brak wymaganych danych');
}
if (!hl_element_access($conn, (int)$_SESSION['login_id'], $element_id)) {
    hl_json_exit('error', 'Nie znaleziono zadania');
}

if ($status_id == 2 || $status_id == 4) {
    // 4 = zarchiwizowane: done_date to data archiwizacji, zadanie znika z planu dnia
    $stmt = $conn->prepare('UPDATE list_elements SET status_id = ?, done_date = NOW() WHERE id = ?');
    if ($status_id == 4) {
        $cleanup = $conn->prepare('DELETE FROM day_plan WHERE element_id = ?');
        $cleanup->bind_param('i', $element_id);
        $cleanup->execute();
    }
} else {
    $stmt = $conn->prepare('UPDATE list_elements SET status_id = ? WHERE id = ?');
}
$stmt->bind_param('ii', $status_id, $element_id);
if ($stmt->execute()) {
    //log
    $before = '';
    $after = 'Status: '.$status_id;
    $object_id = $element_id;
    $object_type="ListElement";
    $action_type = '1';
    $desc = 'Zmieniono status zadania';
    include "../log_stripped_without_conn.php";
    //log
    hl_json_exit('success', 'Pomyślnie zmieniono status zadania');
}
hl_json_exit('error', 'Błąd zmiany statusu zadania');
