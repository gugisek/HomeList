<?php
// Reguły zarządzania kontami i rolami (moduły Użytkownicy i Ustawienia):
// - nie można nadać roli (ani zbudować roli) z wyższymi uprawnieniami niż własne -> brak eskalacji,
// - nie można edytować/usunąć konta z rolą silniejszą od własnej,
// - zawsze musi zostać co najmniej jedno aktywne konto z pełnym dostępem do Użytkowników i Ustawień.

require_once __DIR__ . '/../auth_lib.php';

if (!function_exists('hl_role_row')) {
    function hl_role_row(mysqli $conn, int $roleId): ?array
    {
        $stmt = $conn->prepare('SELECT * FROM user_roles WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // czy poziomy roli (wiersz user_roles albo tablica moduł => poziom) mieszczą się we własnych uprawnieniach
    function hl_role_within_mine(array $levels): bool
    {
        foreach (array_keys(hl_modules()) as $module) {
            if (hl_perm_rank($levels[$module] ?? 5) > hl_perm_rank($_SESSION['perms'][$module] ?? 5)) return false;
        }
        return true;
    }

    // liczba aktywnych kont z pełnym dostępem do Użytkowników i Ustawień (z pominięciem $excludeUserId)
    function hl_full_admin_count(mysqli $conn, int $excludeUserId = 0, int $excludeRoleId = 0): int
    {
        $stmt = $conn->prepare(
            'SELECT COUNT(*) AS cnt FROM users
             JOIN user_roles ON user_roles.id = users.role_id
             JOIN user_status ON user_status.id = users.status_id
             JOIN status_privileges ON status_privileges.id = user_status.privileges
             WHERE user_roles.users = 4 AND user_roles.settings = 4 AND status_privileges.login = 1
               AND users.id <> ? AND user_roles.id <> ?'
        );
        $stmt->bind_param('ii', $excludeUserId, $excludeRoleId);
        $stmt->execute();
        $cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();
        return $cnt;
    }

    function hl_is_full_admin_role(?array $role): bool
    {
        return $role && (int)$role['users'] === 4 && (int)$role['settings'] === 4;
    }

    function hl_status_can_login(mysqli $conn, int $statusId): bool
    {
        $stmt = $conn->prepare('SELECT status_privileges.login FROM user_status JOIN status_privileges ON status_privileges.id = user_status.privileges WHERE user_status.id = ?');
        $stmt->bind_param('i', $statusId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row && (int)$row['login'] === 1;
    }

    function hl_status_exists(mysqli $conn, int $statusId): bool
    {
        $stmt = $conn->prepare('SELECT id FROM user_status WHERE id = ?');
        $stmt->bind_param('i', $statusId);
        $stmt->execute();
        $ok = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $ok;
    }

    // komunikat + powrót do panelu (formularze w popupach użytkowników i ról)
    function hl_admin_back(string $message, string $type): void
    {
        $_SESSION['alert'] = $message;
        $_SESSION['alert_type'] = $type;
        header('Location: ' . hl_app_root_rel() . 'panel.php');
        exit();
    }

    // wspólne opcje <select> ról, które zalogowany może nadać
    function hl_assignable_roles(mysqli $conn): array
    {
        $out = [];
        $result = $conn->query('SELECT * FROM user_roles ORDER BY id ASC');
        while ($row = $result->fetch_assoc()) {
            if (hl_role_within_mine($row)) $out[] = $row;
        }
        return $out;
    }
}
