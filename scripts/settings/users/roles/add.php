<?php
$SECURITY_REQUIRED_MODULE = ['settings', 'add'];
include '../../../security.php';
include "../../../conn_db.php";
require_once 'role_lib.php';
require_once '../../../log_lib.php';

$role = hl_role_from_post();
if ($role['role'] === '') {
    hl_admin_back('Nie wprowadzono wszystkich danych', 'error');
}
if (!hl_role_within_mine($role)) {
    hl_admin_back('Rola nie może mieć wyższych uprawnień niż Twoje', 'error');
}
if (hl_role_name_taken($conn, $role['role'])) {
    hl_admin_back('Rola z taką nazwą już istnieje', 'error');
}

$stmt = $conn->prepare('INSERT INTO user_roles (role, description, dashboard, users, logs, settings, faq, reports, food) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('ssiiiiiii', $role['role'], $role['description'], $role['dashboard'], $role['users'], $role['logs'], $role['settings'], $role['faq'], $role['reports'], $role['food']);
if (!$stmt->execute()) {
    hl_admin_back('Błąd aktualizacji danych', 'error');
}
hl_log($conn, $stmt->insert_id, 'settings', '', hl_role_summary($role), 2, 'Dodano rolę użytkownika');
hl_admin_back('Rola została dodana', 'success');
