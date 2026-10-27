<?php
// Twoje dania (GET): wszystkie nieusunięte dania autora – do przeglądania, edycji i dodawania do dziennika.
// Edycja zmienia przepis na przyszłość; wpisy w dziennikach mają własną kopię wartości, więc historia się nie zmienia.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$res = $conn->query("SELECT p.*, d.id AS dish_id, d.servings, d.updated_at AS dish_updated,
                            (SELECT COUNT(*) FROM food_log l WHERE l.product_id = p.id) AS uses
                     FROM food_dishes d JOIN food_products p ON p.id = d.product_id
                     WHERE d.owner_id = $user_id AND d.archived = 0 ORDER BY d.updated_at DESC LIMIT 200");
$products = [];
while ($row = $res->fetch_assoc()) $products[] = $row;

$rows = hl_food_rows($conn, $user_id, $products);
foreach ($products as $i => $p) {
    $rows[$i]['dish'] = ['id' => (int)$p['dish_id'], 'public' => (bool)$p['public'], 'status' => $p['status'],
                         'servings' => $p['servings'] !== null ? (int)$p['servings'] : null, 'uses' => (int)$p['uses']];
}
hl_food_json('success', '', ['items' => $rows]);
