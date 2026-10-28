<?php
// Kalendarz miesiąca (GET ym = RRRR-MM): płatności w kolejnych dniach – przeszłe zapłacone, od dziś terminy (js/bills.js, PC).
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'bills_lib.php';
session_write_close();

$today = date('Y-m-d');
$ym = (string)($_GET['ym'] ?? substr($today, 0, 7));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym)) hl_bill_json('warning', 'Nieprawidłowy miesiąc');
hl_bill_json('success', '', ['today' => $today, 'ym' => $ym, 'days' => (object)hl_bill_month($conn, (int)$_SESSION['login_id'], $ym, $today)]);
