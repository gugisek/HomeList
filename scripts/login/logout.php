<?php 
session_start();
session_unset();
session_destroy();
session_start();
$_SESSION['alert_type'] = "success";
$_SESSION['alert'] = 'Wylogowano pomyślnie.';

//jeżeli są cookie login_sha i password to je usuwamy
if (isset($_COOKIE['login_sha'])) {
    unset($_COOKIE['login_sha']); // Unset the cookie
    setcookie('login_sha', '', time() - 3600, '/'); // Set the expiration date to one hour ago
}
if (isset($_COOKIE['password'])) {
    unset($_COOKIE['password']); // Unset the cookie
    setcookie('password', '', time() - 3600, '/'); // Set the expiration date to one hour ago
}


header('Location: ../../login.php');
?>