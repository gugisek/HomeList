<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'homelist.rgbpc.pl',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None',
]);
session_start();

if (!isset($_SESSION['logged']) && isset($_COOKIE['remember_me'])) {
    include '../conn_db.php';

    [$selector, $token] = explode(':', $_COOKIE['remember_me']);
    $stmt = $conn->prepare("SELECT * FROM remember_tokens WHERE selector = ?");
    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (hash_equals($row['token_hash'], hash('sha256', $token)) && strtotime($row['expires']) > time()) {
            // Token OK – zaloguj użytkownika
            $_SESSION['logged'] = true;
            $_SESSION['login_id'] = $row['user_id'];
            $_SESSION['last_refresh'] = time();

            // Pobierz dodatkowe dane użytkownika jeśli chcesz
            // np. z tabeli `users` i ustaw $_SESSION['user'], itd.
        } else {
            // Token nieważny – usuń
            setcookie("remember_me", "", time() - 3600, "/");
            $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
            $stmt->bind_param("s", $selector);
            $stmt->execute();
        }
    }
}
?>