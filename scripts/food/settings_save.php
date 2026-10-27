<?php
// Ustawienia modułu (POST enabled?, meals? = "1,3,5", show_on_today?) – zmieniane tylko przesłane pola.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';

$user_id = (int)$_SESSION['login_id'];
$s = hl_food_settings($conn, $user_id);
if (isset($_POST['enabled'])) $s['enabled'] = (bool)(int)$_POST['enabled'];
if (isset($_POST['show_on_today'])) $s['show_on_today'] = (bool)(int)$_POST['show_on_today'];
if (isset($_POST['meals'])) {
    $meals = array_values(array_intersect(array_keys(hl_food_meals()), array_map('intval', explode(',', (string)$_POST['meals']))));
    if (!$meals) hl_food_json('warning', 'Zostaw włączony co najmniej jeden posiłek');
    $s['meals'] = $meals;
}

$enabled = (int)$s['enabled'];
$today = (int)$s['show_on_today'];
$meals = implode(',', $s['meals']);
$stmt = $conn->prepare('INSERT INTO food_settings (user_id, enabled, meals, show_on_today) VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), meals = VALUES(meals), show_on_today = VALUES(show_on_today)');
$stmt->bind_param('iisi', $user_id, $enabled, $meals, $today);
if (!$stmt->execute()) hl_food_json('error', 'Nie udało się zapisać ustawień');
$stmt->close();
hl_food_json('success', 'Zapisano', ['settings' => $s]);
