<?php
// Usunięcie zgłoszenia błędu (razem ze zrzutami) – podstrona Zgłoszenia, poziom full.
$SECURITY_REQUIRED_MODULE = ['reports', 'full'];
include '../security_scripts.php';
require_once '../access.php';
require_once '../log_lib.php';
require_once 'report_lib.php';

$id = (int)($_POST['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM bug_reports WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    hl_json_exit('error', 'Zgłoszenie nie istnieje');
}

$stmt = $conn->prepare('DELETE FROM bug_reports WHERE id = ?');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    hl_json_exit('error', 'Nie udało się usunąć zgłoszenia');
}
foreach (hl_report_shots($row['screenshots']) as $file) @unlink(hl_report_dir() . basename($file));
hl_log($conn, $id, 'bug_report', $row['description'], '', 3, 'Usunięto zgłoszenie błędu');

hl_json_exit('success', 'Zgłoszenie zostało usunięte');
