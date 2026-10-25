<?php
// Odczyt pól roli z formularza components/panel/settings/user_roles/form.php
require_once __DIR__ . '/../../../users/user_admin_lib.php';

function hl_role_from_post(): array
{
    $role = [
        'role' => trim((string)($_POST['name'] ?? '')),
        'description' => trim((string)($_POST['description'] ?? '')),
    ];
    foreach (array_keys(hl_modules()) as $module) {
        $level = (int)($_POST[$module] ?? 5);
        $role[$module] = in_array($level, [1, 2, 3, 4, 5], true) ? $level : 5;
    }
    return $role;
}

function hl_role_summary(array $role): string
{
    $parts = ['Nazwa: ' . $role['role'], 'Opis: ' . $role['description']];
    foreach (hl_modules() as $module => $label) $parts[] = $label . ': ' . $role[$module];
    return implode(' <br/>', $parts);
}

function hl_role_name_taken(mysqli $conn, string $name, int $exceptId = 0): bool
{
    $stmt = $conn->prepare('SELECT id FROM user_roles WHERE role = ? AND id <> ? LIMIT 1');
    $stmt->bind_param('si', $name, $exceptId);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_assoc();
}
