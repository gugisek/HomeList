<?php
$SECURITY_REQUIRED_MODULE = ['settings', 'full'];
include '../../../security.php';
include "../../../conn_db.php";
require_once 'role_lib.php';
require_once '../../../log_lib.php';
require_once '../../../app_settings.php';

$id = (int)($_POST['id'] ?? 0);
$old = hl_role_row($conn, $id);
if (!$old) {
    hl_admin_back('Nie wybrano roli użytkownika', 'warning');
}
if (!hl_role_within_mine($old)) {
    hl_admin_back('Nie możesz usunąć roli z wyższymi uprawnieniami niż Twoje', 'error');
}
$stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM users WHERE role_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
if ((int)$stmt->get_result()->fetch_assoc()['cnt'] > 0) {
    hl_admin_back('Rola jest przypisana do kont – najpierw zmień im rolę', 'error');
}
if ((int)hl_setting($conn, 'registration_role', '3') === $id) {
    hl_admin_back('To domyślna rola nowych kont (Ustawienia > Rejestracja) – wybierz najpierw inną', 'error');
}

$stmt = $conn->prepare('DELETE FROM user_roles WHERE id = ?');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    hl_admin_back('Wystąpił błąd podczas usuwania roli użytkownika', 'error');
}
hl_log($conn, $id, 'settings', hl_role_summary($old), '', 3, 'Usunięto rolę użytkownika');
hl_admin_back('Pomyślnie usunięto rolę użytkownika', 'success');
