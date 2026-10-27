<?php
// Danie domowe (POST id? = id dania, name, items = JSON [{product_id, grams}], cooked_g?, units? = JSON [{label, grams}]).
// Wartości na 100 g = suma składników / waga po ugotowaniu (bez niej: suma wag składników).
// Danie widzą i jedzą domownicy; edytuje autor.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$dish_id = (int)($_POST['id'] ?? 0);
$name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 200);
if ($name === '') hl_food_json('warning', 'Podaj nazwę dania');

$items = [];
$total = ['kcal' => 0, 'protein' => 0, 'fat' => 0, 'carbs' => 0, 'sugars' => 0, 'fiber' => 0, 'sat_fat' => 0, 'salt' => 0];
$raw_weight = 0;
foreach ((array)json_decode((string)($_POST['items'] ?? '[]'), true) as $it) {
    $grams = hl_food_float($it['grams'] ?? '', 0.1, 20000);
    $p = $grams !== null ? hl_food_product($conn, $user_id, (int)($it['product_id'] ?? 0)) : null;
    if (!$p || $p['source'] === 'dish') continue;
    $items[] = [(int)$p['id'], $grams];
    foreach ($total as $k => $_) $total[$k] += (float)($p[$k] ?? 0) * $grams / 100;
    $raw_weight += $grams;
}
if (!$items) hl_food_json('warning', 'Dodaj co najmniej jeden składnik');
if (count($items) > 60) hl_food_json('warning', 'Za dużo składników');

$cooked = isset($_POST['cooked_g']) && trim((string)$_POST['cooked_g']) !== '' ? hl_food_float($_POST['cooked_g'], 1, 50000) : null;
if (isset($_POST['cooked_g']) && trim((string)$_POST['cooked_g']) !== '' && $cooked === null) hl_food_json('warning', 'Nieprawidłowa waga po ugotowaniu');
$weight = $cooked ?? $raw_weight;
$per100 = [];
foreach ($total as $k => $v) $per100[$k] = round($v / $weight * 100, $k === 'kcal' ? 1 : 2);
if ($per100['kcal'] > 950) hl_food_json('warning', 'Waga po ugotowaniu wygląda na zbyt małą');

if ($dish_id) {
    $stmt = $conn->prepare('SELECT * FROM food_dishes WHERE id = ? AND owner_id = ?');
    $stmt->bind_param('ii', $dish_id, $user_id);
    $stmt->execute();
    $dish = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$dish) hl_food_json('error', 'Danie może edytować tylko jego autor');
    $product_id = (int)$dish['product_id'];
}

$search = mb_substr(hl_food_norm($name), 0, 400);
$conn->begin_transaction();
if ($dish_id) {
    $stmt = $conn->prepare('UPDATE food_products SET name = ?, kcal = ?, protein = ?, fat = ?, carbs = ?, sugars = ?, fiber = ?, sat_fat = ?, salt = ?, name_search = ? WHERE id = ?');
    $stmt->bind_param('sddddddddsi', $name, $per100['kcal'], $per100['protein'], $per100['fat'], $per100['carbs'], $per100['sugars'],
        $per100['fiber'], $per100['sat_fat'], $per100['salt'], $search, $product_id);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare('UPDATE food_dishes SET cooked_g = ?, archived = 0 WHERE id = ?');
    $stmt->bind_param('di', $cooked, $dish_id);
    $stmt->execute();
    $stmt->close();
    $conn->query("DELETE FROM food_dish_items WHERE dish_id = $dish_id");
} else {
    $stmt = $conn->prepare("INSERT INTO food_products (source, name, kcal, protein, fat, carbs, sugars, fiber, sat_fat, salt, name_search, owner_id, status)
                            VALUES ('dish', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
    $stmt->bind_param('sddddddddsi', $name, $per100['kcal'], $per100['protein'], $per100['fat'], $per100['carbs'], $per100['sugars'],
        $per100['fiber'], $per100['sat_fat'], $per100['salt'], $search, $user_id);
    $stmt->execute();
    $product_id = $stmt->insert_id;
    $stmt->close();
    $stmt = $conn->prepare('INSERT INTO food_dishes (product_id, owner_id, cooked_g) VALUES (?, ?, ?)');
    $stmt->bind_param('iid', $product_id, $user_id, $cooked);
    $stmt->execute();
    $dish_id = $stmt->insert_id;
    $stmt->close();
}
$ins = $conn->prepare('INSERT INTO food_dish_items (dish_id, product_id, grams, sort) VALUES (?, ?, ?, ?)');
foreach ($items as $i => [$pid, $grams]) {
    $ins->bind_param('iidi', $dish_id, $pid, $grams, $i);
    $ins->execute();
}
$ins->close();

// porcje dania (talerz, miseczka…)
$conn->query("DELETE FROM food_product_units WHERE product_id = $product_id");
$units = array_slice((array)json_decode((string)($_POST['units'] ?? '[]'), true), 0, 5);
$ins = $conn->prepare('INSERT INTO food_product_units (product_id, label, grams, sort) VALUES (?, ?, ?, ?)');
foreach ($units as $i => $u) {
    $label = mb_substr(trim((string)($u['label'] ?? '')), 0, 60);
    $grams = hl_food_float($u['grams'] ?? '', 1, 5000);
    if ($label === '' || $grams === null) continue;
    $ins->bind_param('isdi', $product_id, $label, $grams, $i);
    $ins->execute();
}
$ins->close();
$conn->commit();

$p = hl_food_product($conn, $user_id, $product_id);
hl_food_json('success', 'Zapisano danie', ['product' => hl_food_rows($conn, $user_id, [$p])[0], 'dish_id' => $dish_id]);
