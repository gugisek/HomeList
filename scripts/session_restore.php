<?php
// Start sesji + ciche przywrócenie logowania z ciasteczka remember_me (bez przekierowań).
// Dzięki temu żądania AJAX po wygaśnięciu sesji (np. telefon długo w tle) wykonują się normalnie.
// Zwraca true, jeśli użytkownik jest zalogowany (ważność konta sprawdza dopiero scripts/security.php).
//
// Remember-me jak w panels-mOsiedle-v2 (handle_remember.php): token rotowany przy każdym użyciu,
// przez 120 s po rotacji akceptowany jest też poprzedni (równoległe żądania tego samego klienta),
// a użycie naprawdę starego tokenu = kradzież ciasteczka -> unieważniamy wszystkie tokeny konta.
// Token powstaje dopiero po pełnym zalogowaniu (także po 2FA), więc auto-logowanie nie pyta o 2FA ponownie.

require_once __DIR__ . '/auth_lib.php';

if (!function_exists('hl_session_start')) {
    function hl_session_start(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(['lifetime' => 0] + appCookieParams());
            session_start();
        }
        if (isset($_SESSION['logged'])) return true;

        $cookie = $_COOKIE['remember_me'] ?? null;
        if (!is_string($cookie) || $cookie === '') return false;
        if (strpos($cookie, ':') === false) {
            appClearRememberCookie();
            return false;
        }

        include __DIR__ . '/conn_db.php';
        [$selector, $token] = explode(':', $cookie, 2);

        $stmt = $conn->prepare('SELECT * FROM remember_tokens WHERE selector = ? LIMIT 1');
        $stmt->bind_param('s', $selector);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            appClearRememberCookie();
            return false;
        }

        $submittedHash = hash('sha256', $token);
        $tokenMatches = hash_equals($row['token_hash'], $submittedHash);
        $prevHash = (string)($row['previous_token_hash'] ?? '');
        $withinGrace = !empty($row['rotated_at']) && (time() - strtotime($row['rotated_at'])) <= 120;
        $prevMatches = !$tokenMatches && $prevHash !== '' && $withinGrace && hash_equals($prevHash, $submittedHash);
        $userId = (int)$row['user_id'];

        if (strtotime($row['expires']) <= time()) {
            $stmt = $conn->prepare('DELETE FROM remember_tokens WHERE selector = ?');
            $stmt->bind_param('s', $selector);
            $stmt->execute();
            appClearRememberCookie();
            return false;
        }

        if (!$tokenMatches && !$prevMatches) {
            // ważny selector, ale ani bieżący, ani (w oknie tolerancji) poprzedni token – replay starej wartości
            $stmt = $conn->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            record_login_attempt($conn, $userId, '', false, 'remember_me', 'remember_token_reuse');
            appClearRememberCookie();
            return false;
        }

        $user = hl_auth_user($conn, 'id', $userId);
        if (!$user || (int)$user['can_login'] !== 1) {
            // konto usunięte / zablokowane – token do kosza
            $stmt = $conn->prepare('DELETE FROM remember_tokens WHERE selector = ?');
            $stmt->bind_param('s', $selector);
            $stmt->execute();
            if ($user) record_login_attempt($conn, $userId, (string)$user['login'], false, 'remember_me', 'account_blocked');
            appClearRememberCookie();
            return false;
        }

        hl_login_user($conn, $user, 'remember_me', false);

        if ($tokenMatches) {
            // rotacja: selector zostaje, token się zmienia, poprzedni hash trafia do okna tolerancji
            $newToken = bin2hex(random_bytes(32));
            $newHash = hash('sha256', $newToken);
            $newExpires = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
            $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            $stmt = $conn->prepare('UPDATE remember_tokens SET token_hash = ?, previous_token_hash = ?, rotated_at = NOW(), expires = ?, last_used_at = NOW(), user_agent = ? WHERE selector = ?');
            $stmt->bind_param('sssss', $newHash, $row['token_hash'], $newExpires, $userAgent, $selector);
            $stmt->execute();
            $stmt->close();
            if (!headers_sent()) {
                setcookie('remember_me', $selector . ':' . $newToken, ['expires' => strtotime($newExpires)] + appCookieParams());
            }
        }
        // $prevMatches: równoległe żądanie dostało już nowy token w innej odpowiedzi – nie rotujemy drugi raz,
        // inaczej ciasteczko w przeglądarce mogłoby się rozjechać z bazą i wyglądać jak kradzież.

        return true;
    }
}
