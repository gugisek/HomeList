<?php
// Wejście na karnet (POST bill_id, action add|remove, date – domyślnie dziś). Zwraca liczbę wejść w tym miesiącu i koszt jednego.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'bills_lib.php';

$user_id = (int)$_SESSION['login_id'];
$b = hl_bill_get($conn, $user_id, (int)($_POST['bill_id'] ?? 0));
if (!$b || $b['type'] !== 'pass') hl_bill_json('error', 'Nie znaleziono karnetu');
$date = hl_bill_valid_date($_POST['date'] ?? '') && $_POST['date'] <= date('Y-m-d') ? $_POST['date'] : date('Y-m-d');

if (($_POST['action'] ?? 'add') === 'remove') {
    $stmt = $conn->prepare('DELETE FROM bill_visits WHERE bill_id = ? AND visit_date = ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('is', $b['id'], $date);
} else {
    $stmt = $conn->prepare('INSERT INTO bill_visits (bill_id, visit_date, user_id) VALUES (?, ?, ?)');
    $stmt->bind_param('isi', $b['id'], $date, $user_id);
}
$stmt->execute();
$stmt->close();

$ym = substr($date, 0, 7);
$stmt = $conn->prepare("SELECT COUNT(*) FROM bill_visits WHERE bill_id = ? AND DATE_FORMAT(visit_date, '%Y-%m') = ?");
$stmt->bind_param('is', $b['id'], $ym);
$stmt->execute();
$n = (int)$stmt->get_result()->fetch_row()[0];
$stmt->close();
$monthly = hl_bill_monthly($b, hl_bill_payments($conn, [$b['id']])[$b['id']] ?? []);
hl_bill_json('success', '', ['month' => $ym, 'count' => $n, 'per_visit' => $n ? round($monthly / $n, 2) : null]);
