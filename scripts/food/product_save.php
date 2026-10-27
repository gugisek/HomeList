<?php
// Własny produkt (POST id? name, brand?, code?, kcal, protein, fat, carbs, sugars?, fiber?, sat_fat?, salt?, per_ml?, unit_label?, unit_g?).
// Nowy produkt czeka na akceptację (status pending): widzą go autor i domownicy, pozostali dopiero po akceptacji.
// Edycja: autor (dopóki nie zaakceptowany) albo moderator bazy produktów (moduł food, edit). Po edycji przez autora – znów pending.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$id = (int)($_POST['id'] ?? 0);
$moderator = hl_can('food', 'edit');

$name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 200);
$brand = mb_substr(trim((string)($_POST['brand'] ?? '')), 0, 120) ?: null;
$code = preg_replace('/\D/', '', (string)($_POST['code'] ?? '')) ?: null;
$kcal = hl_food_float($_POST['kcal'] ?? '', 0, 950);
$protein = hl_food_float($_POST['protein'] ?? '', 0, 100);
$fat = hl_food_float($_POST['fat'] ?? '', 0, 100);
$carbs = hl_food_float($_POST['carbs'] ?? '', 0, 100);
$opt = fn($k) => hl_food_float($_POST[$k] ?? '', 0, 100);
$sugars = $opt('sugars');
$fiber = $opt('fiber');
$sat_fat = $opt('sat_fat');
$salt = $opt('salt');
$per_ml = (int)!empty($_POST['per_ml']);
if ($name === '') hl_food_json('warning', 'Podaj nazwę produktu');
if ($kcal === null || $protein === null || $fat === null || $carbs === null) hl_food_json('warning', 'Uzupełnij kalorie i makro na 100 ' . ($per_ml ? 'ml' : 'g'));
if ($protein + $fat + $carbs > 105) hl_food_json('warning', 'Suma białka, tłuszczu i węglowodanów nie może przekraczać 100 g');
if ($code !== null && (strlen($code) < 6 || strlen($code) > 32)) hl_food_json('warning', 'Nieprawidłowy kod kreskowy');

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM food_products WHERE id = ? AND source = 'user'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$old || (!$moderator && ((int)$old['owner_id'] !== $user_id || $old['status'] === 'approved'))) {
        hl_food_json('error', 'Nie możesz edytować tego produktu');
    }
}

if ($code !== null) {
    // ten sam kod już w bazie -> zamiast duplikatu pokazujemy istniejący produkt
    $stmt = $conn->prepare('SELECT p.* FROM food_products p WHERE p.code = ? AND p.id <> ? AND ' . hl_food_visible_sql($conn, $user_id));
    $stmt->bind_param('si', $code, $id);
    $stmt->execute();
    $dup = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($dup) hl_food_json('warning', 'Produkt z tym kodem już jest w bazie', ['product' => hl_food_rows($conn, $user_id, [$dup])[0]]);
    $taken = $conn->query("SELECT id FROM food_products WHERE code = '" . $conn->real_escape_string($code) . "' AND id <> $id")->fetch_row();
    if ($taken) hl_food_json('warning', 'Produkt z tym kodem czeka na akceptację innej osoby');
}

$search = mb_substr(hl_food_norm($name . ' ' . ($brand ?? '')), 0, 400);
if ($id) {
    $status = $moderator ? $old['status'] : 'pending';
    $stmt = $conn->prepare('UPDATE food_products SET name = ?, brand = ?, code = ?, per_ml = ?, kcal = ?, protein = ?, fat = ?, carbs = ?,
                            sugars = ?, fiber = ?, sat_fat = ?, salt = ?, name_search = ?, status = ? WHERE id = ?');
    $stmt->bind_param('sssiddddddddssi', $name, $brand, $code, $per_ml, $kcal, $protein, $fat, $carbs, $sugars, $fiber, $sat_fat, $salt, $search, $status, $id);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO food_products (source, code, name, brand, per_ml, kcal, protein, fat, carbs, sugars, fiber, sat_fat, salt, name_search, owner_id, status)
                            VALUES ('user', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param('sssiddddddddsi', $code, $name, $brand, $per_ml, $kcal, $protein, $fat, $carbs, $sugars, $fiber, $sat_fat, $salt, $search, $user_id);
    if (!$stmt->execute()) hl_food_json('error', 'Nie udało się zapisać produktu');
    $id = $stmt->insert_id;
    $stmt->close();
}

// jedna własna jednostka porcji (np. plaster = 20 g)
$unit_label = mb_substr(trim((string)($_POST['unit_label'] ?? '')), 0, 60);
$unit_g = hl_food_float($_POST['unit_g'] ?? '', 0.1, 5000);
$conn->query("DELETE FROM food_product_units WHERE product_id = $id AND owner_id IS NULL");
if ($unit_label !== '' && $unit_g !== null) {
    $stmt = $conn->prepare('INSERT INTO food_product_units (product_id, label, grams, sort) VALUES (?, ?, ?, 0)');
    $stmt->bind_param('isd', $id, $unit_label, $unit_g);
    $stmt->execute();
    $stmt->close();
}

$p = hl_food_product($conn, $user_id, $id);
hl_food_json('success', 'Zapisano produkt', [
    'product' => hl_food_rows($conn, $user_id, [$p])[0],
    // podpowiedź do formularza – zapisujemy mimo to, etykiety bywają zaokrąglane
    'macros_match' => hl_food_macros_match($kcal, $protein, $fat, $carbs, 0.15, 25, 2 * ($fiber ?? 0)),
]);
