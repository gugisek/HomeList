<?php
$SECURITY_REQUIRED_MODULE = ['faq', 'full'];
include '../../security.php';
include "../../conn_db.php";
require_once "../../users/user_admin_lib.php";
require_once "../../log_lib.php";

$id = (int)($_POST['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM faq WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row_old = $stmt->get_result()->fetch_assoc();
if (!$row_old) {
    hl_admin_back('Nie wybrano FAQ', 'warning');
}
$stmt = $conn->prepare('DELETE FROM faq WHERE id = ?');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    hl_admin_back('Wystąpił błąd podczas usuwania FAQ', 'error');
}
hl_log($conn, $id, 'faq', 'Pytanie: ' . $row_old['question'] . ' <br/> Odpowiedź: ' . $row_old['answer'], '', 3, 'Usunięto FAQ');
hl_admin_back('Pomyślnie usunięto FAQ', 'success');
