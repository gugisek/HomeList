<?php
// Dane do formularza edycji produktu użytkownika (GET id): wszystkie pola, skład i jakość, zdjęcie etykiety, uwaga moderatora.
// Autor – dopóki produkt nie jest zaakceptowany; moderator bazy produktów (food, edit) – zawsze.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM food_products WHERE id = ? AND source = 'user'");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$p || (!hl_can('food', 'edit') && ((int)$p['owner_id'] !== $user_id || $p['status'] === 'approved'))) {
    hl_food_json('error', 'Nie możesz edytować tego produktu');
}
hl_food_json('success', '', ['product' => hl_food_edit_data($conn, $p)]);
