<?php
// Zapis posiłku z „Policz ze zdjęcia” (POST date, meal, items = JSON) – każdy składnik to osobny wpis (source = ai):
//  - dopasowany produkt: { product_id, grams, unit_label?, unit_g? }
//  - same wartości z AI: { name, grams, per100: { kcal, protein, fat, carbs } }
// Zwraca ids – „Cofnij” usuwa je razem (log_delete.php ids).
$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$date = (string)($_POST['date'] ?? '');
$meal = (int)($_POST['meal'] ?? 0);
if (!hl_food_valid_date($date) || !isset(hl_food_meals()[$meal])) hl_food_json('warning', 'Wybierz dzień i posiłek');
$items = json_decode((string)($_POST['items'] ?? ''), true);
if (!is_array($items) || !$items) hl_food_json('warning', 'Nie ma czego dodać');

$rows = [];
foreach (array_slice($items, 0, 30) as $it) {
    if (!is_array($it)) continue;
    $grams = hl_food_float($it['grams'] ?? '', 0.5, 5000);
    if ($grams === null) hl_food_json('warning', 'Podaj ilość każdego składnika');
    if (!empty($it['product_id'])) {
        $p = hl_food_product($conn, $user_id, (int)$it['product_id']);
        if (!$p) hl_food_json('error', 'Nie znaleziono produktu');
        $label = trim((string)($it['unit_label'] ?? '')) !== '' ? mb_substr(trim((string)$it['unit_label']), 0, 60) : null;
        $unitG = $label !== null ? hl_food_float($it['unit_g'] ?? '', 0.1, 5000) : null;
        $rows[] = ['product_id' => (int)$p['id'], 'name' => $p['name'], 'grams' => $grams, 'unit_label' => $unitG === null ? null : $label, 'unit_g' => $unitG] + hl_food_portion($p, $grams);
    } else {
        $p100 = is_array($it['per100'] ?? null) ? $it['per100'] : [];
        $v = ['kcal' => hl_food_float($p100['kcal'] ?? '', 0, 950), 'protein' => hl_food_float($p100['protein'] ?? 0, 0, 100) ?? 0,
              'fat' => hl_food_float($p100['fat'] ?? 0, 0, 100) ?? 0, 'carbs' => hl_food_float($p100['carbs'] ?? 0, 0, 100) ?? 0];
        if ($v['kcal'] === null) hl_food_json('warning', 'Składnik bez kalorii – usuń go albo wybierz produkt z bazy');
        $name = mb_substr(trim((string)($it['name'] ?? '')), 0, 200) ?: 'Składnik';
        $rows[] = ['product_id' => null, 'name' => $name, 'grams' => $grams, 'unit_label' => null, 'unit_g' => null] + hl_food_portion($v, $grams);
    }
}
if (!$rows) hl_food_json('warning', 'Nie ma czego dodać');

$stmt = $conn->prepare('SELECT COALESCE(MAX(sort), 0) FROM food_log WHERE user_id = ? AND log_date = ? AND meal = ?');
$stmt->bind_param('isi', $user_id, $date, $meal);
$stmt->execute();
$sort = (int)$stmt->get_result()->fetch_row()[0];
$stmt->close();

$conn->begin_transaction();
$stmt = $conn->prepare("INSERT INTO food_log (user_id, log_date, meal, product_id, name, grams, unit_label, unit_g, kcal, protein, fat, carbs, sugars, source, sort)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ai', ?)");
$ids = [];
foreach ($rows as $r) {
    $sort++;
    $stmt->bind_param('isiisdsddddddi', $user_id, $date, $meal, $r['product_id'], $r['name'], $r['grams'], $r['unit_label'], $r['unit_g'],
        $r['kcal'], $r['protein'], $r['fat'], $r['carbs'], $r['sugars'], $sort);
    if (!$stmt->execute()) { $conn->rollback(); hl_food_json('error', 'Nie udało się dodać posiłku'); }
    $ids[] = $stmt->insert_id;
}
$stmt->close();
$conn->commit();

hl_food_json('success', '', ['ids' => $ids, 'kcal' => round(array_sum(array_column($rows, 'kcal')))]);
