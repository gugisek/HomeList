<?php
// Ekran główny Opłat (GET): opłaty z kwotą miesięczną, nadchodzące i zaległe terminy, „Wymaga uwagi”, podsumowanie, typy.
// Przy okazji dopisuje minione płatności automatyczne (hl_bill_catch_up).
$SECURITY_REQUIRED_MODULE = ['dashboard', 'read'];
include '../security_scripts.php';
include '../conn_db.php';
require_once 'bills_lib.php';
session_write_close();

hl_bill_json('success', '', hl_bill_overview($conn, (int)$_SESSION['login_id']));
