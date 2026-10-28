<?php
// Szczegóły opłaty (GET id): dane, najbliższe terminy, historia płatności z dodatkami, statystyki pozycji,
// podpowiedzi dodatków (na kartę – z poprzednich doładowań, najczęstsze najpierw).
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'bills_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$today = date('Y-m-d');
$b = hl_bill_get($conn, $user_id, (int)($_GET['id'] ?? 0));
if (!$b) hl_bill_json('error', 'Nie znaleziono opłaty');
hl_bill_catch_up($conn, $user_id, $today);

$stats = hl_bill_item_stats($conn, $b, $today);
$next = array_values(array_filter(hl_bill_upcoming($conn, [$b['id'] => $b], $today, 400), function ($u) { return !$u['paid'] && !$u['skipped']; }));

$history = [];
$rows = $conn->query('SELECT id, due_date, paid_on, amount, status, source FROM bill_payments WHERE bill_id = ' . $b['id'] . ' ORDER BY due_date DESC LIMIT 36')->fetch_all(MYSQLI_ASSOC);
$extras = [];
if ($rows) {
    $res = $conn->query('SELECT payment_id, label, amount FROM bill_payment_extras WHERE payment_id IN (' . implode(',', array_map('intval', array_column($rows, 'id'))) . ')');
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $e) $extras[(int)$e['payment_id']][] = ['label' => $e['label'], 'amount' => (float)$e['amount']];
}
foreach ($rows as $r) {
    $history[] = ['due' => $r['due_date'], 'paid_on' => $r['paid_on'], 'amount' => (float)$r['amount'], 'status' => $r['status'], 'source' => $r['source'],
                  'extras' => $extras[(int)$r['id']] ?? [], 'installment' => hl_bill_installment_no($b, $r['due_date'])];
}

$suggest = [];
foreach ($stats['extras'] as $e) {
    $key = mb_strtolower($e['label']) . '|' . $e['amount'];
    $suggest[$key] = $suggest[$key] ?? ['label' => $e['label'], 'amount' => $e['amount'], 'n' => 0];
    $suggest[$key]['n']++;
}
usort($suggest, function ($a, $b) { return $b['n'] <=> $a['n']; });

hl_bill_json('success', '', [
    'today' => $today, 'bill' => hl_bill_public($b, $stats['monthly']), 'next' => array_slice($next, 0, 6), 'history' => $history,
    'stats' => array_diff_key($stats, ['extras' => 1]), 'extra_suggestions' => array_slice(array_values($suggest), 0, 6),
]);
