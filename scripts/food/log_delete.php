<?php
// Usunięcie wpisu (POST id) albo kilku (POST ids = "1,2,3" – cofnięcie skopiowanego posiłku).
// Zwraca usunięte wpisy – „Cofnij” odsyła pojedynczy wpis do log_add.php jako restore.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'edit'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$ids = array_slice(array_filter(array_map('intval', explode(',', (string)($_POST['ids'] ?? $_POST['id'] ?? '')))), 0, 100);
if (!$ids) hl_food_json('error', 'Nie znaleziono wpisu');
$in = implode(',', $ids);

$res = $conn->query("SELECT * FROM food_log WHERE user_id = $user_id AND id IN ($in)");
$entries = array_map('hl_food_entry', $res->fetch_all(MYSQLI_ASSOC));
if (!$entries) hl_food_json('error', 'Nie znaleziono wpisu');
$conn->query("DELETE FROM food_log WHERE user_id = $user_id AND id IN ($in)");

hl_food_json('success', '', ['entry' => $entries[0], 'entries' => $entries]);
