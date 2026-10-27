<?php
$SECURITY_REQUIRED_MODULE = ['settings', 'edit'];
include '../../../security.php';
include "../../../conn_db.php";
require_once 'role_lib.php';
require_once '../../../log_lib.php';

$id = (int)($_POST['id'] ?? 0);
$role = hl_role_from_post();
$old = hl_role_row($conn, $id);
if (!$old || $role['role'] === '') {
    hl_admin_back('Nie wprowadzono wszystkich danych', 'error');
}
if (!hl_role_within_mine($old) || !hl_role_within_mine($role)) {
    hl_admin_back('Nie możesz edytować roli z wyższymi uprawnieniami niż Twoje', 'error');
}
if (hl_role_name_taken($conn, $role['role'], $id)) {
    hl_admin_back('Rola z taką nazwą już istnieje', 'error');
}
// rola ostatnich pełnych adminów nie może stracić dostępu do Użytkowników / Ustawień
if (hl_is_full_admin_role($old) && !hl_is_full_admin_role($role) && hl_full_admin_count($conn, 0, $id) === 0) {
    hl_admin_back('To jedyna rola z pełnym dostępem przypisana do aktywnych kont – nie można jej odebrać uprawnień do Użytkowników i Ustawień', 'error');
}

$changed = false;
foreach ($role as $col => $val) {
    if ((string)$old[$col] !== (string)$val) $changed = true;
}
if (!$changed) {
    hl_admin_back('Nie wprowadzono nowych danych', 'warning');
}

$stmt = $conn->prepare('UPDATE user_roles SET role = ?, description = ?, dashboard = ?, users = ?, logs = ?, settings = ?, faq = ?, reports = ?, food = ? WHERE id = ?');
$stmt->bind_param('ssiiiiiiii', $role['role'], $role['description'], $role['dashboard'], $role['users'], $role['logs'], $role['settings'], $role['faq'], $role['reports'], $role['food'], $id);
if (!$stmt->execute()) {
    hl_admin_back('Błąd aktualizacji danych', 'error');
}
hl_log($conn, $id, 'settings', hl_role_summary($old), hl_role_summary($role), 1, 'Edytowano rolę użytkownika');
hl_admin_back('Rola została zaktualizowana', 'success');
