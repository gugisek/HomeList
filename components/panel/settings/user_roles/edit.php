<?php /* HL_GUARD */ $SECURITY_REQUIRED_MODULE = ['settings', 'read']; include __DIR__.'/../../../../scripts/security.php'; ?>
<?php
include "../../../../scripts/conn_db.php";
require_once __DIR__ . '/../../../../scripts/users/user_admin_lib.php';
$role_row = hl_role_row($conn, (int)($_GET['id'] ?? 0));
if (!$role_row) {
    echo '<p class="text-sm text-gray-500">Nie znaleziono roli.</p>';
    exit;
}
include 'form.php';
