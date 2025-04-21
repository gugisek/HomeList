<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'homelist.rgbpc.pl',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None',
]);
include '../conn_db.php';

$login_sha = $_POST['email'];
$password_sha = hash('sha256', $_POST['password']);
$remember = isset($_POST['remember-me']);

$sql = "SELECT users.status_id, users.profile_picture, users.name, users.sur_name, status_privileges.login, users.id, user_roles.role, user_roles.dashboard FROM users join user_status on users.status_id=user_status.id join status_privileges on status_privileges.id=user_status.privileges join user_roles on user_roles.id=users.role_id WHERE users.login = '".$login_sha."' AND pswd = '".$password_sha."'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $login = $row['login'];
    $name = $row['name'] . ' ' . $row['sur_name'];
    $login_id = $row['id'];

    if ($login == 1) {
        // Zaloguj użytkownika
        $sql = "UPDATE users SET last_log = NOW() WHERE id = '$login_id'";
        mysqli_query($conn, $sql);

        session_start();
        $_SESSION['logged'] = true;
        $_SESSION['user'] = $name;
        $_SESSION['login'] = $login_sha;
        $_SESSION['login_id'] = $login_id;
        $_SESSION['role'] = $row['role'];
        $_SESSION['dashboard'] = $row['dashboard'];
        $_SESSION['alert'] = 'Zalogowano pomyślnie.';
        $_SESSION['alert_type'] = 'success';
        $_SESSION['profile_picture'] = $row['profile_picture'];

        // Obsługa Remember Me
        if ($remember) {
            $selector = bin2hex(random_bytes(8));
            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 dni

            // Zapisz do bazy
            $stmt = $conn->prepare("INSERT INTO remember_tokens (selector, token_hash, user_id, expires) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $selector, $token_hash, $login_id, $expires);
            $stmt->execute();

            // Ustaw cookie
            $cookie_value = "$selector:$token";
            setcookie("remember_me", $cookie_value, [
                'expires' => strtotime($expires),
                'path' => '/',
                'domain' => 'homelist.rgbpc.pl',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'None'
            ]);
        }

        header('Location: ../../index.php');
    } else {
        session_start();
        $_SESSION['alert_type'] = "warning";
        $_SESSION['alert'] = 'Konto nieaktywne, zablokowane lub wyłączone.<br><br> Skontaktuj się z administratorem.';
        header('Location: ../../login.php');
    }
} else {
    session_start();
    $_SESSION['alert_type'] = "error";
    $_SESSION['alert'] = 'Nieprawidłowy login lub hasło.';
    header('Location: ../../login.php');
}
?>
