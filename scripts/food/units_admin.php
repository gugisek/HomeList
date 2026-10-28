<?php
// Jednostki porcji produktu w „Bazie produktów” (moderator):
//   GET  product_id                                   – produkt + wszystkie jednostki (każdy status, autor, decyzja) + jednostki z etykiety (tylko podgląd)
//   POST product_id, action = save, id?, label, grams – nowa (od razu zaakceptowana) albo poprawka istniejącej (edit)
//   POST product_id, action = approve | reject, id     – decyzja (edit)
//   POST product_id, action = delete, id               – usunięcie (full)
// Każda zmiana trafia do archiwum zmian (logs, object_type food_unit); odpowiedź = aktualny stan jak przy GET.
$SECURITY_REQUIRED_MODULE = ['food', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
require_once '../log_lib.php';

$me = (int)$_SESSION['login_id'];
$product_id = (int)($_REQUEST['product_id'] ?? 0);
$p = $conn->query("SELECT * FROM food_products WHERE id = $product_id")->fetch_assoc();
if (!$p) hl_food_json('error', 'Nie znaleziono produktu');
$u = $p['per_ml'] ? 'ml' : 'g';
$fmt = fn($v) => rtrim(rtrim(number_format((float)$v, 1, ',', ''), '0'), ',');

$state = function (string $msg = '') use ($conn, $p, $product_id) {
    $units = [];
    $res = $conn->query("SELECT un.*, o.name AS owner_name, o.sur_name AS owner_sur, r.name AS rev_name, r.sur_name AS rev_sur
                         FROM food_product_units un LEFT JOIN users o ON o.id = un.owner_id LEFT JOIN users r ON r.id = un.reviewed_by
                         WHERE un.product_id = $product_id ORDER BY un.sort, un.id");
    while ($r = $res->fetch_assoc()) {
        $units[] = ['id' => (int)$r['id'], 'label' => $r['label'], 'grams' => (float)$r['grams'], 'status' => $r['status'],
                    // owner_id NULL = jednostka z seeda / od autora produktu (formularz) / z dania
                    'owner' => $r['owner_id'] !== null ? trim($r['owner_name'] . ' ' . $r['owner_sur']) ?: 'Usunięte konto' : null,
                    'reviewer' => $r['reviewed_by'] !== null ? trim($r['rev_name'] . ' ' . $r['rev_sur']) : null,
                    'created_at' => $r['created_at'], 'reviewed_at' => $r['reviewed_at']];
    }
    // porcja / opakowanie z etykiety (OFF, formularz produktu) – zmienia je import albo edycja produktu, tu tylko podgląd
    $label = [];
    if ($p['serving_g'] !== null) $label[] = ['label' => $p['serving_label'] ?: 'porcja', 'grams' => (float)$p['serving_g']];
    if ($p['package_g'] !== null) $label[] = ['label' => 'opakowanie', 'grams' => (float)$p['package_g']];
    hl_food_json('success', $msg, [
        'product' => ['id' => (int)$p['id'], 'name' => $p['name'], 'brand' => $p['brand'], 'code' => $p['code'], 'quantity' => $p['quantity'],
                      'source' => $p['source'], 'per_ml' => (bool)$p['per_ml'], 'kcal' => (float)$p['kcal']],
        'units' => $units, 'label_units' => $label,
        'can_edit' => hl_can('food', 'edit'), 'can_delete' => hl_can('food', 'full'),
    ]);
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') $state();

$action = (string)($_POST['action'] ?? '');
$need = ['save' => 'edit', 'approve' => 'edit', 'reject' => 'edit', 'delete' => 'full'][$action] ?? null;
if ($need === null) hl_food_json('error', 'Nieznana akcja');
if (!hl_can('food', $need)) hl_food_json('error', 'Brak uprawnień');

$id = (int)($_POST['id'] ?? 0);
$old = null;
if ($id) {
    $old = $conn->query("SELECT * FROM food_product_units WHERE id = $id AND product_id = $product_id")->fetch_assoc();
    if (!$old) hl_food_json('error', 'Nie znaleziono jednostki');
}
$desc = fn($row) => $row['label'] . ' = ' . $fmt($row['grams']) . " $u<br/>Produkt: " . $p['name'] . ' (#' . $product_id . ')<br/>Status: ' . $row['status'];

if ($action === 'delete') {
    if (!$old) hl_food_json('error', 'Nie znaleziono jednostki');
    $conn->query("DELETE FROM food_product_units WHERE id = $id");
    hl_log($conn, $id, 'food_unit', $desc($old), '', 3, 'Usunięto jednostkę porcji');
    $state('Usunięto jednostkę');
}

if ($action === 'approve' || $action === 'reject') {
    if (!$old) hl_food_json('error', 'Nie znaleziono jednostki');
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $conn->query("UPDATE food_product_units SET status = '$status', reviewed_by = $me, reviewed_at = NOW() WHERE id = $id");
    hl_log($conn, $id, 'food_unit', $desc($old), $desc(['status' => $status] + $old), 1, ($status === 'approved' ? 'Zaakceptowano' : 'Odrzucono') . ' jednostkę porcji');
    $state($status === 'approved' ? 'Zaakceptowano jednostkę' : 'Odrzucono jednostkę');
}

// zapis: te same zasady co przy zgłaszaniu (unit_save.php)
$label = mb_substr(mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string)($_POST['label'] ?? ''))), 'UTF-8'), 0, 30);
$grams = hl_food_float($_POST['grams'] ?? '', 1, 5000);
if ($label === '' || preg_match('/^\d/', $label)) hl_food_json('warning', 'Podaj nazwę jednostki, np. opakowanie albo sztuka');
if ($grams === null) hl_food_json('warning', "Podaj wagę jednostki (1–5000 $u)");
$grams = round($grams, 1);
$stmt = $conn->prepare('SELECT id FROM food_product_units WHERE product_id = ? AND label = ? AND ABS(grams - ?) < 0.05 AND id <> ?');
$stmt->bind_param('isdi', $product_id, $label, $grams, $id);
$stmt->execute();
$dup = $stmt->get_result()->fetch_row();
$stmt->close();
if ($dup) hl_food_json('warning', 'Taka jednostka już jest');

if ($old) {
    $stmt = $conn->prepare('UPDATE food_product_units SET label = ?, grams = ? WHERE id = ?');
    $stmt->bind_param('sdi', $label, $grams, $id);
    $stmt->execute();
    $stmt->close();
    hl_log($conn, $id, 'food_unit', $desc($old), $desc(['label' => $label, 'grams' => $grams] + $old), 1, 'Poprawiono jednostkę porcji');
    $state('Zapisano jednostkę');
}
// dodana przez moderatora – od razu dla wszystkich, w historii jako „zatwierdzone od razu”
$sort = (int)$conn->query("SELECT COALESCE(MAX(sort), 0) FROM food_product_units WHERE product_id = $product_id")->fetch_row()[0];
$stmt = $conn->prepare("INSERT INTO food_product_units (product_id, label, grams, sort, owner_id, status, reviewed_by, reviewed_at)
                        VALUES (?, ?, ?, ?, ?, 'approved', ?, NOW())");
$stmt->bind_param('isdiii', $product_id, $label, $grams, $sort, $me, $me);
$stmt->execute();
$new = $stmt->insert_id;
$stmt->close();
hl_log($conn, $new, 'food_unit', '', $desc(['label' => $label, 'grams' => $grams, 'status' => 'approved']), 0, 'Dodano jednostkę porcji');
$state('Dodano jednostkę');
