<?php 
session_start();
if(isset($_SESSION['logged'])){
    header('Location: index.php');
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
<?php
$title = "logowanie do konta";
include 'components/head.php'; ?>    
</head>
<body>
<?php
// if (isset($_COOKIE['login_sha']) && isset($_COOKIE['password'])) {
//     // Automatyczne logowanie – dane z ciasteczek
//     $login = $_COOKIE['login_sha'];
//     $password = $_COOKIE['password'];

//     // Przekierowanie do logowania z przekazaniem danych metodą POST
//     echo '
//     <form id="autoLogin" method="post" action="scripts/login/login_script.php" style="display:none;">
//         <input type="hidden" name="email" value="'.htmlspecialchars($login).'">
//         <input type="hidden" name="password" value="'.htmlspecialchars($password).'">
//     </form>
//     <script>
//         document.getElementById("autoLogin").submit();
//     </script>';
//     exit;
// }
?>
<?php
if (isset($_COOKIE['remember_me'])) {
    include 'scripts/conn_db.php';

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
            $sql = "SELECT users.status_id, users.profile_picture, users.name, users.sur_name, status_privileges.login, users.id, user_roles.role, user_roles.dashboard FROM users join user_status on users.status_id=user_status.id join status_privileges on status_privileges.id=user_status.privileges join user_roles on user_roles.id=users.role_id WHERE users.id = '" . $_SESSION['login_id'] . "'";
            $result = mysqli_query($conn, $sql);
            $row2 = mysqli_fetch_assoc($result);
            $_SESSION['user'] = $row2['name'] . ' ' . $row2['sur_name'];
            $_SESSION['role'] = $row2['role'];
            $_SESSION['dashboard'] = $row2['dashboard'];
            $_SESSION['alert'] = 'Zalogowano pomyślnie.';
            $_SESSION['alert_type'] = 'success';
            $_SESSION['profile_picture'] = $row2['profile_picture'];
            header('Location: login.php');
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
    <?php include 'components/alert.php'; ?>
    
    <?php include 'components/login/login_form.php'; ?>
    <?php include 'components/footer.php'; ?>
    <script>
        AOS.init();
    </script>
</body>
</html>