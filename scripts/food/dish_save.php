<?php
// Danie domowe (POST id? = id dania, name, photo? (plik), photo_remove?, items = JSON [{product_id, grams, unit_label?, unit_g?}], cooked_g?,
//               servings? = na ile porcji dzielimy garnek, unit_label? = nazwa porcji (porcja, talerz…), public = 0|1).
// Wartości na 100 g = suma składników / waga po ugotowaniu (bez niej: suma wag składników); porcja = waga / servings.
// Widoczność: dom (autor + domownicy) zawsze; public = 1 – pozostali po akceptacji w „Bazie produktów” (moderator: od razu).
// Edytuje autor; zmiana udostępnionego dania wraca do akceptacji.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$moderator = hl_can('food', 'edit');
$dish_id = (int)($_POST['id'] ?? 0);
$name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 200);
$public = (int)!empty($_POST['public']);
if ($name === '') hl_food_json('warning', 'Podaj nazwę dania');

$items = [];
$total = ['kcal' => 0, 'protein' => 0, 'fat' => 0, 'carbs' => 0, 'sugars' => 0, 'fiber' => 0, 'sat_fat' => 0, 'salt' => 0];
$raw_weight = 0;
foreach ((array)json_decode((string)($_POST['items'] ?? '[]'), true) as $it) {
    $grams = hl_food_float($it['grams'] ?? '', 0.1, 20000);
    $p = $grams !== null ? hl_food_product($conn, $user_id, (int)($it['product_id'] ?? 0)) : null;
    if (!$p || $p['source'] === 'dish') continue;
    // jednostka tylko do opisu w edytorze („3 × sztuka”) – liczą się gramy
    $ul = mb_substr(trim((string)($it['unit_label'] ?? '')), 0, 60);
    $ug = $ul !== '' ? hl_food_float($it['unit_g'] ?? '', 0.1, 5000) : null;
    $items[] = [(int)$p['id'], $grams, $ug !== null ? $ul : null, $ug];
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

$servings = (int)($_POST['servings'] ?? 0);
$servings = $servings >= 1 && $servings <= 50 ? $servings : null;
$unit_label = mb_strtolower(mb_substr(trim((string)($_POST['unit_label'] ?? '')), 0, 30), 'UTF-8') ?: 'porcja';

$old = null;
if ($dish_id) {
    $stmt = $conn->prepare('SELECT d.*, p.public, p.status FROM food_dishes d JOIN food_products p ON p.id = d.product_id WHERE d.id = ? AND d.owner_id = ?');
    $stmt->bind_param('ii', $dish_id, $user_id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$old) hl_food_json('error', 'Danie może edytować tylko jego autor');
    $product_id = (int)$old['product_id'];
}

// status: danie domowe nie wymaga akceptacji; udostępnione – akceptacja (moderator zatwierdza sam siebie);
// zapis już zaakceptowanego udostępnionego dania przez autora wraca do kolejki, bo zmienia wartości dla wszystkich
$status = !$public || $moderator ? 'approved' : 'pending';
$reviewer = $public && $moderator ? $user_id : null;

// zdjęcie: nowe (photo), usunięcie (photo_remove = 1) albo bez zmian; plik sprawdzamy przed zapisem dania
$photo_new = null;
if (!empty($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $saved = hl_food_save_photo($_FILES['photo'], $user_id);
    if (!$saved['ok']) hl_food_json('warning', $saved['error']);
    $photo_new = $saved['file'];
}
$photo_remove = !empty($_POST['photo_remove']);

$search = mb_substr(hl_food_norm($name), 0, 400);
$conn->begin_transaction();
if ($dish_id) {
    $stmt = $conn->prepare('UPDATE food_products SET name = ?, kcal = ?, protein = ?, fat = ?, carbs = ?, sugars = ?, fiber = ?, sat_fat = ?, salt = ?,
                            name_search = ?, public = ?, status = ?, reviewed_by = ?, reviewed_at = IF(? IS NULL, NULL, NOW()) WHERE id = ?');
    $stmt->bind_param('sddddddddsisiii', $name, $per100['kcal'], $per100['protein'], $per100['fat'], $per100['carbs'], $per100['sugars'],
        $per100['fiber'], $per100['sat_fat'], $per100['salt'], $search, $public, $status, $reviewer, $reviewer, $product_id);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare('UPDATE food_dishes SET cooked_g = ?, servings = ?, archived = 0 WHERE id = ?');
    $stmt->bind_param('dii', $cooked, $servings, $dish_id);
    $stmt->execute();
    $stmt->close();
    $conn->query("DELETE FROM food_dish_items WHERE dish_id = $dish_id");
} else {
    $stmt = $conn->prepare("INSERT INTO food_products (source, name, kcal, protein, fat, carbs, sugars, fiber, sat_fat, salt, name_search, owner_id, public, status, reviewed_by, reviewed_at)
                            VALUES ('dish', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, IF(? IS NULL, NULL, NOW()))");
    $stmt->bind_param('sddddddddsiisii', $name, $per100['kcal'], $per100['protein'], $per100['fat'], $per100['carbs'], $per100['sugars'],
        $per100['fiber'], $per100['sat_fat'], $per100['salt'], $search, $user_id, $public, $status, $reviewer, $reviewer);
    $stmt->execute();
    $product_id = $stmt->insert_id;
    $stmt->close();
    $stmt = $conn->prepare('INSERT INTO food_dishes (product_id, owner_id, cooked_g, servings) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('iidi', $product_id, $user_id, $cooked, $servings);
    $stmt->execute();
    $dish_id = $stmt->insert_id;
    $stmt->close();
}
$ins = $conn->prepare('INSERT INTO food_dish_items (dish_id, product_id, grams, unit_label, unit_g, sort) VALUES (?, ?, ?, ?, ?, ?)');
foreach ($items as $i => [$pid, $grams, $ul, $ug]) {
    $ins->bind_param('iidsdi', $dish_id, $pid, $grams, $ul, $ug, $i);
    $ins->execute();
}
$ins->close();

// porcja dania = cały garnek / liczba porcji
$conn->query("DELETE FROM food_product_units WHERE product_id = $product_id AND owner_id IS NULL");
if ($servings !== null) {
    $portion = round($weight / $servings, 1);
    if ($portion >= 1) {
        $ins = $conn->prepare('INSERT INTO food_product_units (product_id, label, grams, sort) VALUES (?, ?, ?, 0)');
        $ins->bind_param('isd', $product_id, $unit_label, $portion);
        $ins->execute();
        $ins->close();
    }
}
$conn->commit();
if ($photo_new !== null || $photo_remove) {
    $old_photo = $conn->query("SELECT photo FROM food_products WHERE id = $product_id")->fetch_row()[0] ?? null;
    $stmt = $conn->prepare('UPDATE food_products SET photo = ? WHERE id = ?');
    $stmt->bind_param('si', $photo_new, $product_id);
    $stmt->execute();
    $stmt->close();
    if ($old_photo !== $photo_new) hl_food_delete_photo($old_photo);
}

$p = hl_food_product($conn, $user_id, $product_id);
$msg = $public && $status === 'pending' ? 'Zapisano – w domu od razu, dla wszystkich po akceptacji' : 'Zapisano danie';
hl_food_json('success', $msg, ['product' => hl_food_rows($conn, $user_id, [$p])[0], 'dish_id' => $dish_id]);
