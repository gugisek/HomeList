<?php
// Jednostka porcji zgłoszona przez użytkownika (np. opakowanie, którego nie zna Open Food Facts):
//   POST product_id, label, grams      – dodanie; autor widzi ją od razu, pozostali po akceptacji (moderator: od razu dla wszystkich)
//   POST product_id, delete = id       – usunięcie własnej, jeszcze niezaakceptowanej jednostki
// Zwraca produkt (jak product.php, bez składu) z aktualną listą jednostek.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$p = hl_food_product($conn, $user_id, $product_id);
if (!$p) hl_food_json('error', 'Nie znaleziono produktu');
$done = fn(string $msg) => hl_food_json('success', $msg, ['product' => hl_food_rows($conn, $user_id, [hl_food_product($conn, $user_id, $product_id)])[0]]);

if (isset($_POST['delete'])) {
    $id = (int)$_POST['delete'];
    $stmt = $conn->prepare("DELETE FROM food_product_units WHERE id = ? AND product_id = ? AND owner_id = ? AND status <> 'approved'");
    $stmt->bind_param('iii', $id, $product_id, $user_id);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    if (!$ok) hl_food_json('error', 'Nie możesz usunąć tej jednostki');
    $done('Usunięto jednostkę');
}

$label = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string)($_POST['label'] ?? ''))), 'UTF-8');
$label = mb_substr($label, 0, 30);
$grams = hl_food_float($_POST['grams'] ?? '', 1, 5000);
if ($label === '' || preg_match('/^\d/', $label)) hl_food_json('warning', 'Podaj nazwę jednostki, np. opakowanie albo sztuka');
if ($grams === null) hl_food_json('warning', 'Podaj wagę jednostki (1–5000 ' . ($p['per_ml'] ? 'ml' : 'g') . ')');
$grams = round($grams, 1);

// już jest taka sama (np. z etykiety albo zgłoszona wcześniej) – nic nie dodajemy
foreach (hl_food_units($conn, [$p], $user_id)[$product_id] as $u) {
    if (empty($u['base']) && $u['label'] === $label && abs($u['grams'] - $grams) < 0.05) $done('Ta jednostka już jest');
}
$mine = (int)$conn->query("SELECT COUNT(*) FROM food_product_units WHERE product_id = $product_id AND owner_id = $user_id AND status <> 'approved'")->fetch_row()[0];
if ($mine >= 5) hl_food_json('warning', 'Masz już 5 jednostek czekających na akceptację dla tego produktu');

$status = hl_can('food', 'edit') ? 'approved' : 'pending';
$stmt = $conn->prepare('INSERT INTO food_product_units (product_id, label, grams, sort, owner_id, status) VALUES (?, ?, ?, 0, ?, ?)');
$stmt->bind_param('isdis', $product_id, $label, $grams, $user_id, $status);
$stmt->execute();
$stmt->close();
$done($status === 'approved' ? 'Dodano jednostkę' : 'Dodano – u Ciebie od razu, dla innych po akceptacji');
