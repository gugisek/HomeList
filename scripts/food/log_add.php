<?php
// Dodanie wpisu do dziennika (POST date, meal, …):
//  - produkt: product_id, grams, unit_label?, unit_g?, source? (manual|barcode)
//  - szybkie kcal: name?, kcal, protein?, fat?, carbs?
//  - cofnięcie usunięcia: restore = JSON wpisu zwróconego przez log_delete.php
// Nazwa i makro zapisywane jako kopia – późniejsza zmiana produktu nie zmienia historii.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$date = (string)($_POST['date'] ?? '');
$meal = (int)($_POST['meal'] ?? 0);
if (!hl_food_valid_date($date) || !isset(hl_food_meals()[$meal])) hl_food_json('warning', 'Wybierz dzień i posiłek');

$product_id = null;
$grams = $unit_g = null;
$unit_label = null;
$source = in_array($_POST['source'] ?? '', ['manual', 'barcode'], true) ? $_POST['source'] : 'manual';

if (isset($_POST['restore'])) {
    $r = json_decode((string)$_POST['restore'], true);
    if (!is_array($r)) hl_food_json('error', 'Nie udało się przywrócić wpisu');
    $product_id = isset($r['product_id']) ? (int)$r['product_id'] : null;
    $name = mb_substr(trim((string)($r['name'] ?? '')), 0, 200) ?: 'Wpis';
    $grams = isset($r['grams']) ? hl_food_float($r['grams'], 0.1, 5000) : null;
    $unit_label = isset($r['unit_label']) ? mb_substr((string)$r['unit_label'], 0, 60) : null;
    $unit_g = isset($r['unit_g']) ? hl_food_float($r['unit_g'], 0.1, 5000) : null;
    $m = ['kcal' => hl_food_float($r['kcal'] ?? '', 0, 20000), 'protein' => hl_food_float($r['protein'] ?? 0, 0, 2000),
          'fat' => hl_food_float($r['fat'] ?? 0, 0, 2000), 'carbs' => hl_food_float($r['carbs'] ?? 0, 0, 2000)];
    if (in_array(null, $m, true)) hl_food_json('error', 'Nie udało się przywrócić wpisu');
    $m['sugars'] = isset($r['sugars']) ? hl_food_float($r['sugars'], 0, 2000) : null;
    $source = in_array($r['source'] ?? '', ['manual', 'quick', 'barcode', 'copy', 'ai'], true) ? $r['source'] : 'manual';
} elseif (!empty($_POST['product_id'])) {
    $p = hl_food_product($conn, $user_id, (int)$_POST['product_id']);
    if (!$p) hl_food_json('error', 'Nie znaleziono produktu');
    $grams = hl_food_float($_POST['grams'] ?? '', 1, 5000);
    if ($grams === null) hl_food_json('warning', 'Podaj gramaturę');
    $product_id = (int)$p['id'];
    $name = $p['name'];
    $unit_label = trim((string)($_POST['unit_label'] ?? '')) !== '' ? mb_substr(trim($_POST['unit_label']), 0, 60) : null;
    $unit_g = $unit_label !== null ? hl_food_float($_POST['unit_g'] ?? '', 0.1, 5000) : null;
    if ($unit_g === null) $unit_label = null;
    $m = hl_food_portion($p, $grams);
} else {
    $kcal = hl_food_float($_POST['kcal'] ?? '', 0, 10000);
    if ($kcal === null) hl_food_json('warning', 'Podaj kalorie');
    $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 200) ?: 'Szybkie dodanie';
    $m = ['kcal' => $kcal, 'protein' => hl_food_float($_POST['protein'] ?? '', 0, 1000) ?? 0,
          'fat' => hl_food_float($_POST['fat'] ?? '', 0, 1000) ?? 0, 'carbs' => hl_food_float($_POST['carbs'] ?? '', 0, 1000) ?? 0, 'sugars' => null];
    $source = 'quick';
}

$stmt = $conn->prepare('SELECT COALESCE(MAX(sort), 0) + 1 FROM food_log WHERE user_id = ? AND log_date = ? AND meal = ?');
$stmt->bind_param('isi', $user_id, $date, $meal);
$stmt->execute();
$sort = (int)$stmt->get_result()->fetch_row()[0];
$stmt->close();

$stmt = $conn->prepare('INSERT INTO food_log (user_id, log_date, meal, product_id, name, grams, unit_label, unit_g, kcal, protein, fat, carbs, sugars, source, sort)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('isiisdsddddddsi', $user_id, $date, $meal, $product_id, $name, $grams, $unit_label, $unit_g,
    $m['kcal'], $m['protein'], $m['fat'], $m['carbs'], $m['sugars'], $source, $sort);
if (!$stmt->execute()) hl_food_json('error', 'Nie udało się dodać wpisu');
$id = $stmt->insert_id;
$stmt->close();

$res = $conn->query('SELECT * FROM food_log WHERE id = ' . (int)$id);
hl_food_json('success', '', ['entry' => hl_food_entry($res->fetch_assoc())]);
