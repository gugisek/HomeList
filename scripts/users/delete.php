<?php
$SECURITY_REQUIRED_MODULE = ['users', 'full'];
include '../security.php';
include "../conn_db.php";
require_once 'user_admin_lib.php';
require_once '../log_lib.php';

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    hl_admin_back('Nie wybrano użytkownika', 'warning');
}
if ($id === (int)$_SESSION['login_id']) {
    hl_admin_back('Nie możesz usunąć samego siebie!', 'error');
}

$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row_old = $stmt->get_result()->fetch_assoc();
if (!$row_old) {
    hl_admin_back('Nie znaleziono użytkownika', 'error');
}
$role = hl_role_row($conn, (int)$row_old['role_id']);
if ($role && !hl_role_within_mine($role)) {
    hl_admin_back('Nie możesz usunąć konta z wyższymi uprawnieniami niż Twoje', 'error');
}
if (hl_is_full_admin_role($role) && hl_full_admin_count($conn, $id) === 0) {
    hl_admin_back('Nie można usunąć ostatniego konta z pełnym dostępem', 'error');
}

$stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    hl_admin_back('Wystąpił błąd podczas usuwania użytkownika', 'error');
}

// dane powiązane z kontem; listy właściciela zachowują się jak usunięte (owner_id = 0), zadania zostają w historii
$cleanup = [
    'DELETE FROM remember_tokens WHERE user_id = ?',
    'DELETE FROM list_invities WHERE user_id = ?',
    'DELETE FROM day_plan WHERE user_id = ?',
    'DELETE FROM user_two_factor_settings WHERE user_id = ?',
    'DELETE FROM user_two_factor_backup_codes WHERE user_id = ?',
    'DELETE FROM activation_tokens WHERE user_id = ?',
    'DELETE FROM password_reset_tokens WHERE user_id = ?',
    'DELETE FROM push_subscriptions WHERE user_id = ?',
    'DELETE FROM push_settings WHERE user_id = ?',
    "UPDATE lists SET owner_id = '0' WHERE owner_id = ?",
];
foreach ($cleanup as $sql) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) continue;
    $stmt->bind_param('i', $id);
    $stmt->execute();
}

hl_log($conn, $id, 'users',
    'Imię: ' . $row_old['name'] . ' ' . $row_old['sur_name'] . ' <br/> Email: ' . $row_old['mail'] . '<br/> Rola: ' . $row_old['role_id'],
    '', 3, 'Usunięto użytkownika');

hl_admin_back('Pomyślnie usunięto użytkownika', 'success');
