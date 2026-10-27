<?php
// Dzień w dzienniku (GET date, strip?): posiłki z wpisami, sumy, cel, wczorajsze posiłki, ustawienia;
// strip=1 dokłada pasek dni (sumy kcal z ~4 tygodni).
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'food_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$date = (string)($_GET['date'] ?? date('Y-m-d'));
if (!hl_food_valid_date($date)) hl_food_json('warning', 'Nieprawidłowa data');

hl_food_json('success', '', hl_food_payload($conn, $user_id, $date, !empty($_GET['strip'])));
