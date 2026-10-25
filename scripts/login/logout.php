<?php
require_once __DIR__ . '/../auth_lib.php';
include __DIR__ . '/../conn_db.php';

$cancel2fa = isset($_GET['cancel']);
hl_destroy_session($conn);
hl_flash_session($cancel2fa ? 'Logowanie anulowane.' : 'Zostałeś wylogowany.', 'success');

header('Location: ../../login.php');
