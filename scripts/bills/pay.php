<?php
// Płatność terminu (POST bill_id, due, action):
//   paid – odhaczenie; amount (obowiązkowe przy kwocie zmiennej, inaczej cena z historii), extras = JSON [[label, kwota], …], paid_on
//   skip – pominięcie terminu (np. zawieszony miesiąc) – znika z nadchodzących, nie liczy się w statystykach
//   undo – cofnięcie; zwraca usuniętą płatność (restore – odesłana z action=paid przywraca ją 1:1)
$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'bills_lib.php';

$user_id = (int)$_SESSION['login_id'];
$b = hl_bill_get($conn, $user_id, (int)($_POST['bill_id'] ?? 0));
if (!$b) hl_bill_json('error', 'Nie znaleziono opłaty');
$due = (string)($_POST['due'] ?? '');
if (!hl_bill_valid_date($due)) hl_bill_json('warning', 'Nieprawidłowy termin');
$action = (string)($_POST['action'] ?? 'paid');

if ($action === 'undo') {
    $row = hl_bill_unpay($conn, $b, $due);
    if (!$row) hl_bill_json('warning', 'Ta płatność nie była odhaczona');
    $extrasSum = array_sum(array_map(function ($e) { return (float)$e[1]; }, $row['extras']));
    hl_bill_json('success', '', ['restore' => ['amount' => round((float)$row['amount'] - $extrasSum, 2), 'extras' => $row['extras'],
        'paid_on' => $row['paid_on'], 'status' => $row['status'], 'source' => $row['source']]]);
}

// termin musi wynikać z harmonogramu (albo mieć już wiersz) – inaczej dałoby się dopisać płatność z dowolną datą
$lastPaid = hl_bill_last_paid($conn, [$b['id']])[$b['id']] ?? null;
$stmt = $conn->prepare('SELECT COUNT(*) FROM bill_payments WHERE bill_id = ? AND due_date = ?');
$stmt->bind_param('is', $b['id'], $due);
$stmt->execute();
$hasRow = (int)$stmt->get_result()->fetch_row()[0] > 0;
$stmt->close();
if (!$hasRow && !in_array($due, hl_bill_due_dates($b, $due, $due, $lastPaid), true)) hl_bill_json('warning', 'Ta opłata nie ma płatności w tym dniu');

if ($action === 'skip') {
    hl_bill_pay($conn, $b, $due, 0, $user_id, [], 'skipped');
    hl_bill_json('success', 'Pominięto płatność', ['bill_id' => $b['id'], 'due' => $due]);
}
if ($action !== 'paid') hl_bill_json('warning', 'Nieznana akcja');

$raw = trim((string)($_POST['amount'] ?? ''));
$amount = $raw !== '' ? hl_bill_float($raw, 0, 1000000) : null;
if ($raw !== '' && $amount === null) hl_bill_json('warning', 'Nieprawidłowa kwota');
if ($amount === null && $b['amount_variable']) hl_bill_json('warning', 'Podaj kwotę z rachunku');
if ($amount === null) $amount = hl_bill_price_on(hl_bill_prices($conn, [$b['id']])[$b['id']] ?? [], $b, $due);

$extras = [];
foreach (array_slice((array)json_decode((string)($_POST['extras'] ?? '[]'), true), 0, 20) as $e) {
    if (!is_array($e) || count($e) < 2) continue;
    $v = hl_bill_float($e[1], 0.01, 100000);
    if ($v !== null && trim((string)$e[0]) !== '') $extras[] = [(string)$e[0], $v];
}
$paidOn = hl_bill_valid_date($_POST['paid_on'] ?? '') && $_POST['paid_on'] <= date('Y-m-d') ? $_POST['paid_on'] : date('Y-m-d');
$source = in_array($_POST['source'] ?? '', ['auto', 'estimate'], true) ? $_POST['source'] : 'manual';

hl_bill_pay($conn, $b, $due, $amount, $user_id, $extras, 'paid', $source, $paidOn);
hl_bill_json('success', $b['name'] . ' · opłacone', ['bill_id' => $b['id'], 'due' => $due, 'amount' => round($amount + array_sum(array_column($extras, 1)), 2)]);
