<?php
// Edycja wpisu (POST id, grams?, meal?, unit_label?, unit_g?; wpis bez gramatury: kcal, protein, fat, carbs, name).
// Zmiana gramatury skaluje zapisaną kopię makro – wartości produktu z chwili dodania zostają.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'edit'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$id = (int)($_POST['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM food_log WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$e = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$e) hl_food_json('error', 'Nie znaleziono wpisu');

$meal = isset($_POST['meal']) ? (int)$_POST['meal'] : (int)$e['meal'];
if (!isset(hl_food_meals()[$meal])) hl_food_json('warning', 'Nieprawidłowy posiłek');
$m = ['kcal' => (float)$e['kcal'], 'protein' => (float)$e['protein'], 'fat' => (float)$e['fat'], 'carbs' => (float)$e['carbs']];
$grams = $e['grams'] !== null ? (float)$e['grams'] : null;
$unit_label = $e['unit_label'];
$unit_g = $e['unit_g'] !== null ? (float)$e['unit_g'] : null;
$name = $e['name'];

if ($grams !== null) {
    if (isset($_POST['grams'])) {
        $new = hl_food_float($_POST['grams'], 1, 5000);
        if ($new === null) hl_food_json('warning', 'Podaj gramaturę');
        foreach ($m as $k => $v) $m[$k] = round($v * $new / $grams, $k === 'kcal' ? 1 : 2);
        $grams = $new;
    }
    if (array_key_exists('unit_label', $_POST)) {
        $unit_label = trim((string)$_POST['unit_label']) !== '' ? mb_substr(trim($_POST['unit_label']), 0, 60) : null;
        $unit_g = $unit_label !== null ? hl_food_float($_POST['unit_g'] ?? '', 0.1, 5000) : null;
        if ($unit_g === null) $unit_label = null;
    }
} else {
    if (isset($_POST['kcal'])) {
        $kcal = hl_food_float($_POST['kcal'], 0, 10000);
        if ($kcal === null) hl_food_json('warning', 'Podaj kalorie');
        $m = ['kcal' => $kcal, 'protein' => hl_food_float($_POST['protein'] ?? '', 0, 1000) ?? 0,
              'fat' => hl_food_float($_POST['fat'] ?? '', 0, 1000) ?? 0, 'carbs' => hl_food_float($_POST['carbs'] ?? '', 0, 1000) ?? 0];
    }
    if (isset($_POST['name']) && trim($_POST['name']) !== '') $name = mb_substr(trim($_POST['name']), 0, 200);
}

$sort = (int)$e['sort'];
if ($meal !== (int)$e['meal']) {
    // przeniesiony wpis trafia na koniec nowego posiłku
    $stmt = $conn->prepare('SELECT COALESCE(MAX(sort), 0) + 1 FROM food_log WHERE user_id = ? AND log_date = ? AND meal = ?');
    $stmt->bind_param('isi', $user_id, $e['log_date'], $meal);
    $stmt->execute();
    $sort = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
}

$stmt = $conn->prepare('UPDATE food_log SET meal = ?, name = ?, grams = ?, unit_label = ?, unit_g = ?, kcal = ?, protein = ?, fat = ?, carbs = ?, sort = ?
                        WHERE id = ? AND user_id = ?');
$stmt->bind_param('isdsdddddiii', $meal, $name, $grams, $unit_label, $unit_g, $m['kcal'], $m['protein'], $m['fat'], $m['carbs'], $sort, $id, $user_id);
if (!$stmt->execute()) hl_food_json('error', 'Nie udało się zapisać');
$stmt->close();

$res = $conn->query('SELECT * FROM food_log WHERE id = ' . $id);
hl_food_json('success', '', ['entry' => hl_food_entry($res->fetch_assoc())]);
