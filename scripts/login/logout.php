<?php 
session_start();
session_unset();
session_destroy();

// Wyloguj użytkownika (usuń cookies)
$cookies_to_clear = ['login_sha', 'password'];
foreach ($cookies_to_clear as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => 'homelist.rgbpc.pl',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None'
        ]);
    }
}

// Komunikat o wylogowaniu
session_start();
$_SESSION['alert_type'] = "success";
$_SESSION['alert'] = 'Wylogowano pomyślnie.';

// Przekierowanie
header('Location: ../../login.php');
exit;
?>
