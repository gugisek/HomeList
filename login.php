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
if (isset($_COOKIE['login_sha']) && isset($_COOKIE['password'])) {
    // Automatyczne logowanie – dane z ciasteczek
    $login = $_COOKIE['login_sha'];
    $password = $_COOKIE['password'];

    // Przekierowanie do logowania z przekazaniem danych metodą POST
    echo '
    <form id="autoLogin" method="post" action="scripts/login/login_script.php" style="display:none;">
        <input type="hidden" name="email" value="'.htmlspecialchars($login).'">
        <input type="hidden" name="password" value="'.htmlspecialchars($password).'">
    </form>
    <script>
        document.getElementById("autoLogin").submit();
    </script>';
    exit;
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