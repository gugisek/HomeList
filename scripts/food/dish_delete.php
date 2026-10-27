<?php
// Usunięcie dania (POST id = id dania) – tylko autor. Danie jest archiwizowane, bo wpisy w dzienniku mogą do niego prowadzić.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'edit'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$id = (int)($_POST['id'] ?? 0);
$stmt = $conn->prepare('UPDATE food_dishes SET archived = 1 WHERE id = ? AND owner_id = ?');
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$ok = $stmt->affected_rows > 0;
$stmt->close();
$ok ? hl_food_json('success', 'Usunięto danie') : hl_food_json('error', 'Danie może usunąć tylko jego autor');
