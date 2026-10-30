<?php
// Własny produkt (POST id? name, brand?, code?, kcal, protein, fat, carbs, sugars?, fiber?, sat_fat?, salt?, per_ml?, unit_label?, unit_g?, package_g?,
//                 skład i jakość: ingredients?, allergens? (po przecinku), traces?, nutriscore?, nova?, palm_oil?, diet? (vegan|vegetarian|none),
//                 label_photo? (plik – zdjęcie etykiety do weryfikacji), label_photo_remove?, approve? (moderator: zapisz i zaakceptuj)).
// Nowy produkt czeka na akceptację (status pending): widzą go autor i domownicy, pozostali dopiero po akceptacji.
// Edycja: autor (dopóki nie zaakceptowany) albo moderator bazy produktów (moduł food, edit). Po edycji przez autora – znów pending.
// Produkt dodany przez moderatora jest od razu zaakceptowany (reviewed_by = on sam), poprawki moderatora trafiają do archiwum zmian.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
require_once '../log_lib.php';

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
$u = $per_ml ? 'ml' : 'g';
$package_raw = trim((string)($_POST['package_g'] ?? ''));
$package_g = hl_food_float($package_raw, 1, 5000);
$err = hl_food_product_check($_POST, hl_food_edit_values($_POST));
if ($err !== null) hl_food_json('warning', $err);
$details = hl_food_details_from_post($_POST);

$old = null;
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

// zdjęcie etykiety: sprawdzamy przed zapisem produktu, żeby błąd pliku nie zostawił połowy zmian
$label_new = null;
if (!empty($_FILES['label_photo']) && ($_FILES['label_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $saved = hl_food_save_photo($_FILES['label_photo'], $user_id, 'label');
    if (!$saved['ok']) hl_food_json('warning', $saved['error']);
    $label_new = $saved['file'];
}
$label_remove = !empty($_POST['label_photo_remove']);

$fmt = fn($v) => rtrim(rtrim(number_format((float)$v, 1, ',', ''), '0'), ',');
$quantity = $package_g !== null ? $fmt($package_g) . ' ' . $u : null;
$search = mb_substr(hl_food_norm($name . ' ' . ($brand ?? '')), 0, 400);
$approve = $moderator && !empty($_POST['approve']);
if ($id) {
    // edycja autora wraca do kolejki – poprzednia decyzja moderatora przestaje obowiązywać;
    // uwaga z odrzucenia zostaje, żeby moderator widział, czego dotyczyła poprawka
    $status = $approve ? 'approved' : ($moderator ? $old['status'] : 'pending');
    $stmt = $conn->prepare("UPDATE food_products SET name = ?, brand = ?, code = ?, per_ml = ?, kcal = ?, protein = ?, fat = ?, carbs = ?,
                            sugars = ?, fiber = ?, sat_fat = ?, salt = ?, package_g = ?, quantity = ?, name_search = ?, status = ? WHERE id = ?");
    $stmt->bind_param('sssidddddddddsssi', $name, $brand, $code, $per_ml, $kcal, $protein, $fat, $carbs, $sugars, $fiber, $sat_fat, $salt,
                      $package_g, $quantity, $search, $status, $id);
    $stmt->execute();
    $stmt->close();
    if ($approve && $old['status'] !== 'approved') {
        $conn->query("UPDATE food_products SET reviewed_by = $user_id, reviewed_at = NOW(), review_note = NULL WHERE id = $id");
    } elseif ($status === 'pending') {
        $conn->query("UPDATE food_products SET reviewed_by = NULL, reviewed_at = NULL WHERE id = $id");
    }
} else {
    $status = $moderator ? 'approved' : 'pending';
    $stmt = $conn->prepare("INSERT INTO food_products (source, code, name, brand, per_ml, kcal, protein, fat, carbs, sugars, fiber, sat_fat, salt, package_g, quantity,
                                                       name_search, owner_id, status, reviewed_by, reviewed_at)
                            VALUES ('user', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, IF(? = 'approved', ?, NULL), IF(? = 'approved', NOW(), NULL))");
    $stmt->bind_param('sssidddddddddssissis',$code, $name, $brand, $per_ml, $kcal, $protein, $fat, $carbs, $sugars, $fiber, $sat_fat, $salt,
                      $package_g, $quantity, $search, $user_id, $status, $status, $user_id, $status);
    if (!$stmt->execute()) {
        hl_food_delete_photo($label_new);
        hl_food_json('error', 'Nie udało się zapisać produktu');
    }
    $id = $stmt->insert_id;
    $stmt->close();
}

hl_food_details_save($conn, $id, $details);

if ($label_new !== null || $label_remove) {
    $stmt = $conn->prepare('UPDATE food_products SET label_photo = ? WHERE id = ?');
    $stmt->bind_param('si', $label_new, $id);
    $stmt->execute();
    $stmt->close();
    if ($old && $old['label_photo'] !== $label_new) hl_food_delete_photo($old['label_photo']);
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

$what = $name . ($brand ? ' · ' . $brand : '') . '<br/>' . $fmt($kcal) . ' kcal / 100 ' . $u . ' · B ' . $fmt($protein) . ' · T ' . $fmt($fat) . ' · W ' . $fmt($carbs);
if ($moderator && $old && (int)$old['owner_id'] !== $user_id) {
    $was = $old['name'] . ($old['brand'] ? ' · ' . $old['brand'] : '') . '<br/>' . $fmt($old['kcal']) . ' kcal / 100 ' . ($old['per_ml'] ? 'ml' : 'g')
         . ' · B ' . $fmt($old['protein']) . ' · T ' . $fmt($old['fat']) . ' · W ' . $fmt($old['carbs']) . '<br/>Status: ' . $old['status'];
    hl_log($conn, $id, 'food_product', $was, $what . '<br/>Status: ' . $status, 1, $approve && $old['status'] !== 'approved' ? 'Poprawiono i zaakceptowano produkt' : 'Poprawiono produkt użytkownika');
} elseif ($moderator && !$old) {
    hl_log($conn, $id, 'food_product', '', $what . '<br/>Status: approved', 0, 'Dodano produkt do bazy');
}

$p = $conn->query("SELECT * FROM food_products WHERE id = $id")->fetch_assoc();
$msg = $approve && $old && $old['status'] !== 'approved' ? 'Zapisano i zaakceptowano produkt' : 'Zapisano produkt';
hl_food_json('success', $msg, [
    'product' => hl_food_rows($conn, $user_id, [$p])[0],
    // podpowiedź do formularza – zapisujemy mimo to, etykiety bywają zaokrąglane
    'macros_match' => hl_food_macros_match($kcal, $protein, $fat, $carbs, 0.15, 25, 2 * ($fiber ?? 0)),
]);
