<?php
session_start();
session_unset();
session_destroy();
// Usuń remember_me token
if (isset($_COOKIE['remember_me'])) {
    include '../conn_db.php';
    [$selector] = explode(':', $_COOKIE['remember_me']);
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
    $stmt->bind_param("s", $selector);
    $stmt->execute();

    setcookie("remember_me", "", time() - 3600, "/");
}

header('Location: ../../login.php');
