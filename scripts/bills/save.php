<?php
// Dodanie / edycja opłaty (POST, pola jak w hl_bill_from_input; id = edycja).
// Nowa: next_due (najbliższa płatność) obowiązkowo; paid_from („płacę od”) albo paid_count (raty już zapłacone) uzupełnia historię wstecz.
// Edycja: next_due tylko gdy zmienia się harmonogram; zmiana kwoty zapisuje nową cenę w historii.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'bills_lib.php';

$user_id = (int)$_SESSION['login_id'];
$id = (int)($_POST['id'] ?? 0);
$existing = null;
if ($id) {
    $existing = hl_bill_get($conn, $user_id, $id);
    if (!$existing) hl_bill_json('error', 'Nie znaleziono opłaty');
}
$data = hl_bill_from_input($_POST, $existing);
if (is_string($data)) hl_bill_json('warning', $data);

$conn->begin_transaction();
try {
    $id = hl_bill_save($conn, $user_id, $data['fields'], $data['extra'], $existing);
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    hl_bill_json('error', 'Nie udało się zapisać opłaty');
}
hl_bill_json('success', $existing ? 'Zapisano zmiany' : 'Dodano opłatę', ['bill' => hl_bill_public(hl_bill_get($conn, $user_id, $id))]);
