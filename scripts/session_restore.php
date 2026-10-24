<?php
// Start sesji + ciche przywrócenie logowania z ciasteczka remember_me (bez przekierowań).
// Dzięki temu żądania AJAX po wygaśnięciu sesji (np. telefon długo w tle) wykonują się normalnie.
// Zwraca true, jeśli użytkownik jest zalogowany.
function hl_session_start()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['logged'])) return true;
    if (empty($_COOKIE['remember_me']) || strpos($_COOKIE['remember_me'], ':') === false) return false;

    include __DIR__.'/conn_db.php';

    [$selector, $token] = explode(':', $_COOKIE['remember_me'], 2);
    $stmt = $conn->prepare("SELECT * FROM remember_tokens WHERE selector = ?");
    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !hash_equals($row['token_hash'], hash('sha256', $token)) || strtotime($row['expires']) <= time()) {
        // Token nieważny – usuń
        setcookie("remember_me", "", time() - 3600, "/");
        if ($row) {
            $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
            $stmt->bind_param("s", $selector);
            $stmt->execute();
        }
        return false;
    }

    $stmt = $conn->prepare("SELECT users.profile_picture, users.name, users.sur_name, status_privileges.login, users.role_id, user_roles.role, user_roles.dashboard FROM users join user_status on users.status_id=user_status.id join status_privileges on status_privileges.id=user_status.privileges join user_roles on user_roles.id=users.role_id WHERE users.id = ?");
    $stmt->bind_param("i", $row['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    // konto usunięte / zablokowane – nie logujemy
    if (!$user || $user['login'] != 1) return false;

    $_SESSION['logged'] = true;
    $_SESSION['login_id'] = $row['user_id'];
    $_SESSION['last_refresh'] = time();
    $_SESSION['user'] = $user['name'] . ' ' . $user['sur_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['dashboard'] = $user['dashboard'];
    $_SESSION['profile_picture'] = $user['profile_picture'];
    return true;
}
