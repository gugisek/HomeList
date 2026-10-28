<?php
// Statystyki (GET period 12|year|all): faktyczne płatności per miesiąc i typ, porównanie z poprzednim okresem,
// najdroższe pozycje od początku, podwyżki z ostatniego roku i podpowiedzi „Do przemyślenia”.
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'bills_lib.php';
session_write_close();

$user_id = (int)$_SESSION['login_id'];
$today = date('Y-m-d');
hl_bill_catch_up($conn, $user_id, $today);
hl_bill_json('success', '', hl_bill_stats_payload($conn, $user_id, (string)($_GET['period'] ?? '12'), $today));
