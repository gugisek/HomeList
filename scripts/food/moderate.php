<?php
// Moderacja produktów użytkowników (moduł „Baza produktów”):
//   GET  – lista oczekujących (read)
//   POST id, action = approve | reject (edit) | delete (full)
// Usunięcie nie rusza dziennika – wpisy mają własną kopię nazwy i makro.
$SECURITY_REQUIRED_MODULE = ['food', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $res = $conn->query("SELECT p.*, u.name AS owner_name FROM food_products p LEFT JOIN users u ON u.id = p.owner_id
                         WHERE p.source = 'user' AND p.status = 'pending' ORDER BY p.id LIMIT 200");
    $items = [];
    while ($p = $res->fetch_assoc()) {
        $items[] = ['id' => (int)$p['id'], 'name' => $p['name'], 'brand' => $p['brand'], 'code' => $p['code'], 'owner' => $p['owner_name'],
                    'per_ml' => (bool)$p['per_ml'], 'kcal' => (float)$p['kcal'], 'protein' => (float)$p['protein'], 'fat' => (float)$p['fat'],
                    'carbs' => (float)$p['carbs'], 'created_at' => $p['created_at'],
                    'macros_match' => hl_food_macros_match((float)$p['kcal'], (float)$p['protein'], (float)$p['fat'], (float)$p['carbs'], 0.15, 25, 2 * (float)$p['fiber'])];
    }
    hl_food_json('success', '', ['items' => $items]);
}

$id = (int)($_POST['id'] ?? 0);
$action = (string)($_POST['action'] ?? '');
$need = ['approve' => 'edit', 'reject' => 'edit', 'delete' => 'full'][$action] ?? null;
if ($need === null) hl_food_json('error', 'Nieznana akcja');
if (!hl_can('food', $need)) hl_food_json('error', 'Brak uprawnień');

if ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM food_products WHERE id = ? AND source = 'user'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    if ($ok) {
        $conn->query("DELETE FROM food_product_units WHERE product_id = $id");
        $conn->query("DELETE FROM food_product_details WHERE product_id = $id");
    }
} else {
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $stmt = $conn->prepare("UPDATE food_products SET status = ? WHERE id = ? AND source = 'user'");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0 && $conn->query("SELECT 1 FROM food_products WHERE id = $id AND source = 'user'")->num_rows > 0;
    $stmt->close();
}
$ok ? hl_food_json('success', ['approve' => 'Zaakceptowano produkt', 'reject' => 'Odrzucono produkt', 'delete' => 'Usunięto produkt'][$action])
    : hl_food_json('error', 'Nie znaleziono produktu');
