<?php
// Wspólne funkcje logowania i uprawnień: pobranie konta, założenie sesji, remember-me, poziomy ról.
// Używane przez scripts/login/*, scripts/session_restore.php i scripts/security.php.

require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/login_attempts.php';

if (!function_exists('hl_auth_user')) {
    // Moduły z poziomami dostępu w user_roles (kolumna => etykieta w edytorze ról)
    function hl_modules(): array
    {
        return [
            'dashboard' => 'Listy zadań',
            'users' => 'Użytkownicy',
            'logs' => 'Archiwum i logowania',
            'settings' => 'Ustawienia',
            'faq' => 'FAQ',
        ];
    }

    // user_role_privileges: 1 read, 2 edit, 3 add, 4 full, 5 none -> ranga rosnąca z uprawnieniami
    function hl_perm_rank($level): int
    {
        $map = ['none' => 0, 'read' => 1, 'edit' => 2, 'add' => 3, 'full' => 4];
        if (is_string($level) && isset($map[$level])) return $map[$level];
        return [1 => 1, 2 => 2, 3 => 3, 4 => 4][(int)$level] ?? 0;
    }

    function hl_can(string $module, string $level = 'read'): bool
    {
        $perms = $_SESSION['perms'] ?? [];
        return hl_perm_rank($perms[$module] ?? 5) >= hl_perm_rank($level);
    }

    // Konto + rola + czy status pozwala się logować. $by = 'id' | 'login'
    function hl_auth_user(mysqli $conn, string $by, $value): ?array
    {
        $where = $by === 'id' ? 'users.id = ?' : 'LOWER(users.login) = LOWER(?)';
        $stmt = $conn->prepare(
            "SELECT users.id, users.login, users.mail, users.name, users.sur_name, users.pswd, users.security_stamp,
                    users.status_id, users.profile_picture, users.role_id,
                    user_roles.role, user_roles.dashboard, user_roles.users AS perm_users, user_roles.logs,
                    user_roles.settings, user_roles.faq, COALESCE(status_privileges.login, 0) AS can_login
             FROM users
             LEFT JOIN user_status ON user_status.id = users.status_id
             LEFT JOIN status_privileges ON status_privileges.id = user_status.privileges
             LEFT JOIN user_roles ON user_roles.id = users.role_id
             WHERE $where LIMIT 1"
        );
        $type = $by === 'id' ? 'i' : 's';
        $stmt->bind_param($type, $value);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // Odcisk danych uwierzytelniających: zmiana hasła albo security_stamp unieważnia stare sesje.
    function hl_auth_fingerprint(array $user): string
    {
        return substr(hash('sha256', $user['pswd'] . '|' . $user['security_stamp']), 0, 32);
    }

    // Dane konta -> $_SESSION (przy logowaniu i przy każdym odświeżeniu w security.php)
    function hl_apply_user_session(array $user): void
    {
        $_SESSION['login_id'] = (int)$user['id'];
        $_SESSION['login'] = $user['login'];
        $_SESSION['user'] = trim($user['name'] . ' ' . $user['sur_name']);
        $_SESSION['role'] = $user['role'];
        $_SESSION['role_id'] = (int)$user['role_id'];
        $_SESSION['dashboard'] = $user['dashboard'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
        $_SESSION['perms'] = [
            'dashboard' => (int)$user['dashboard'],
            'users' => (int)$user['perm_users'],
            'logs' => (int)$user['logs'],
            'settings' => (int)$user['settings'],
            'faq' => (int)$user['faq'],
        ];
        $_SESSION['auth_fp'] = hl_auth_fingerprint($user);
    }

    // Pełne zalogowanie po pozytywnej weryfikacji (hasło / 2FA / remember-me)
    function hl_login_user(mysqli $conn, array $user, string $method, bool $remember): void
    {
        // dokładnie jedno ciasteczko sesji w odpowiedzi (podwójny Set-Cookie PHPSESSID potrafi mylić proxy)
        if (!headers_sent()) header_remove('Set-Cookie');
        session_regenerate_id(true);
        unset($_SESSION['pending_2fa']);

        hl_apply_user_session($user);
        $_SESSION['logged'] = true;
        $_SESSION['last_refresh'] = time();

        $userId = (int)$user['id'];
        $stmt = $conn->prepare('UPDATE users SET last_log = NOW() WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        record_login_attempt($conn, $userId, (string)$user['login'], true, $method);

        if ($remember) hl_issue_remember($conn, $userId);
    }

    function hl_issue_remember(mysqli $conn, int $userId): void
    {
        $selector = bin2hex(random_bytes(8));
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
        $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        $stmt = $conn->prepare('INSERT INTO remember_tokens (selector, token_hash, user_id, expires, created_at, last_used_at, user_agent) VALUES (?, ?, ?, ?, NOW(), NOW(), ?)');
        $stmt->bind_param('ssiss', $selector, $tokenHash, $userId, $expires, $userAgent);
        $stmt->execute();
        $stmt->close();

        if (!headers_sent()) {
            setcookie('remember_me', $selector . ':' . $token, ['expires' => strtotime($expires)] + appCookieParams());
        }
    }

    // Selector remember_me bieżącego urządzenia (do oznaczenia "to urządzenie" w profilu)
    function hl_current_remember_selector(): ?string
    {
        $cookie = $_COOKIE['remember_me'] ?? '';
        if (!is_string($cookie) || strpos($cookie, ':') === false) return null;
        return explode(':', $cookie, 2)[0];
    }

    // Unieważnia wszystkie sesje i zapamiętane urządzenia konta. $keepCurrent – bieżąca sesja
    // (i jej remember_me) zostaje ważna, np. po zmianie własnego hasła albo "wyloguj inne urządzenia".
    function hl_revoke_user_sessions(mysqli $conn, int $userId, bool $keepCurrent = false): void
    {
        $keepSelector = $keepCurrent ? (hl_current_remember_selector() ?? '') : '';
        $stmt = $conn->prepare('DELETE FROM remember_tokens WHERE user_id = ? AND selector <> ?');
        $stmt->bind_param('is', $userId, $keepSelector);
        $stmt->execute();
        $stmt->close();

        $stamp = bin2hex(random_bytes(16));
        $stmt = $conn->prepare('UPDATE users SET security_stamp = ? WHERE id = ?');
        $stmt->bind_param('si', $stamp, $userId);
        $stmt->execute();
        $stmt->close();

        if ($keepCurrent && (int)($_SESSION['login_id'] ?? 0) === $userId) {
            $user = hl_auth_user($conn, 'id', $userId);
            if ($user) hl_apply_user_session($user);
        }
    }

    // Wylogowanie bieżącej sesji (bez komunikatu – patrz scripts/login/logout.php)
    function hl_destroy_session(mysqli $conn): void
    {
        $selector = hl_current_remember_selector();
        if ($selector !== null) {
            $stmt = $conn->prepare('DELETE FROM remember_tokens WHERE selector = ?');
            $stmt->bind_param('s', $selector);
            $stmt->execute();
            $stmt->close();
        }
        appClearRememberCookie();

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (!headers_sent()) {
                setcookie(session_name(), '', ['expires' => time() - 42000] + appCookieParams());
            }
            session_destroy();
        }
    }

    // Nowa, pusta sesja tylko po to, żeby przenieść komunikat na stronę logowania
    function hl_flash_session(string $message, string $type): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name(HL_SESSION_NAME);
            session_set_cookie_params(['lifetime' => 0] + appCookieParams());
            session_start();
        }
        if (!headers_sent()) session_regenerate_id(true);
        $_SESSION['alert'] = $message;
        $_SESSION['alert_type'] = $type;
    }

    function hl_user_has_2fa(mysqli $conn, int $userId): bool
    {
        $stmt = $conn->prepare('SELECT is_enabled FROM user_two_factor_settings WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row && (int)$row['is_enabled'] === 1;
    }
}
