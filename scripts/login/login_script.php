<?php
include '../conn_db.php';
$login_sha = $_POST['email'];
//jeżeli jest cookie z hasłem to pobieramy hasło z posta bez szyfrowania
if (isset($_COOKIE['password'])) {
    $password_sha = $_POST['password'];
} else {
    //jeżeli nie ma cookie to hasło jest szyfrowane
    $password_sha = hash('sha256', $_POST['password']);
}

$remember = isset($_POST['remember-me']);

echo $login_sha.'<br>';
echo $password_sha.'<br>';

$sql = "SELECT * FROM users WHERE login = '".$login_sha."' AND pswd = '".$password_sha."'";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0)
{
    $sql = "SELECT users.status_id, users.profile_picture, users.name, users.sur_name, status_privileges.login, users.id, user_roles.role, user_roles.dashboard FROM users join user_status on users.status_id=user_status.id join status_privileges on status_privileges.id=user_status.privileges join user_roles on user_roles.id=users.role_id WHERE users.login = '".$login_sha."' AND pswd = '".$password_sha."'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $login = $row['login'];
    $name = ''.$row['name'].' '.$row['sur_name'].'';
    $login_id = $row['id'];
    if($login == 1)
    {

        $sql = "UPDATE users SET last_log = NOW() WHERE id = '".$login_id."'";
        mysqli_query($conn, $sql);
        
        // jeżeli użytkownik zaznaczył zapamiętaj mnie to ustawiamy cookie na 30 dni
        if ($remember) {
            // Ustaw cookie na 30 dni
            setcookie('login_sha', $login_sha, time() + (30 * 24 * 60 * 60), "/");
            setcookie('password', $password_sha, time() + (30 * 24 * 60 * 60), "/"); // Uwaga: niezalecane
        }

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

        header('Location: ../../index.php');
    }
    else
    {
        if (isset($_COOKIE['login_sha'])) {
            unset($_COOKIE['login_sha']); // Unset the cookie
            setcookie('login_sha', '', time() - 3600, '/'); // Set the expiration date to one hour ago
        }
        if (isset($_COOKIE['password'])) {
            unset($_COOKIE['password']); // Unset the cookie
            setcookie('password', '', time() - 3600, '/'); // Set the expiration date to one hour ago
        }
        session_start();
        $_SESSION['alert_type'] = "warning";
        $_SESSION['alert'] = 'Konto nieaktywne, zablokowane lub wyłączone.<br><br> Skontaktuj się z administratorem.';
        header('Location: ../../login.php');
    }
}
else
{
    //usuwanie cookie jeżeli są błędne dane logowania
    if (isset($_COOKIE['login_sha'])) {
        unset($_COOKIE['login_sha']); // Unset the cookie
        setcookie('login_sha', '', time() - 3600, '/'); // Set the expiration date to one hour ago
    }
    if (isset($_COOKIE['password'])) {
        unset($_COOKIE['password']); // Unset the cookie
        setcookie('password', '', time() - 3600, '/'); // Set the expiration date to one hour ago
    }
    session_start();
    $_SESSION['alert_type'] = "error";
    $_SESSION['alert'] = 'Nieprawidłowy login lub hasło.';
    header('Location: ../../login.php');
}
 ?>