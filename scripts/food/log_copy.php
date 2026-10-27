<?php
// Kopiowanie posiłku (POST from_date, from_meal, to_date, to_meal) – np. „Jak wczoraj”.
// GET from_date, from_meal = podgląd do przycisku (liczba produktów i kcal), bez zapisu.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$in = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$from = (string)($in['from_date'] ?? '');
$from_meal = (int)($in['from_meal'] ?? 0);
if (!hl_food_valid_date($from) || !isset(hl_food_meals()[$from_meal])) hl_food_json('warning', 'Nieprawidłowy posiłek');

$stmt = $conn->prepare('SELECT * FROM food_log WHERE user_id = ? AND log_date = ? AND meal = ? ORDER BY sort, id');
$stmt->bind_param('isi', $user_id, $from, $from_meal);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hl_food_json('success', '', ['count' => count($rows), 'kcal' => round(array_sum(array_column($rows, 'kcal')))]);
}
if (!hl_can('dashboard', 'add')) hl_food_json('error', 'Brak uprawnień');
if (!$rows) hl_food_json('warning', 'Ten posiłek był pusty');

$to = (string)($_POST['to_date'] ?? '');
$to_meal = (int)($_POST['to_meal'] ?? 0);
if (!hl_food_valid_date($to) || !isset(hl_food_meals()[$to_meal])) hl_food_json('warning', 'Nieprawidłowy posiłek');

$stmt = $conn->prepare('SELECT COALESCE(MAX(sort), 0) FROM food_log WHERE user_id = ? AND log_date = ? AND meal = ?');
$stmt->bind_param('isi', $user_id, $to, $to_meal);
$stmt->execute();
$sort = (int)$stmt->get_result()->fetch_row()[0];
$stmt->close();

$ins = $conn->prepare("INSERT INTO food_log (user_id, log_date, meal, product_id, name, grams, unit_label, unit_g, kcal, protein, fat, carbs, sugars, source, sort)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'copy', ?)");
$ids = [];
foreach ($rows as $r) {
    $sort++;
    $pid = $r['product_id'] !== null ? (int)$r['product_id'] : null;
    $ins->bind_param('isiisdsddddddi', $user_id, $to, $to_meal, $pid, $r['name'], $r['grams'], $r['unit_label'], $r['unit_g'],
        $r['kcal'], $r['protein'], $r['fat'], $r['carbs'], $r['sugars'], $sort);
    $ins->execute();
    $ids[] = $ins->insert_id;
}
$ins->close();
hl_food_json('success', 'Skopiowano ' . count($ids) . ' ' . (count($ids) === 1 ? 'produkt' : (count($ids) < 5 ? 'produkty' : 'produktów')), ['ids' => $ids]);
