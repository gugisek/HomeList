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
$name = $_POST['name'];

if ($login_sha != "" || $password_sha != "" || $name != "") {
    $sql = "Select * from users where login = '" . $login_sha . "'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) == 0) {
        $sql = "INSERT INTO users (login, pswd, role_id, status_id, create_date, name) VALUES ('$login_sha', '" . $password_sha . "', '3', '1', NOW(), '" . $name . "')";
        if (mysqli_query($conn, $sql)) {
            session_start();
            $_SESSION['alert_type'] = "success";
            $_SESSION['alert'] = 'Rejestracja przebiegła pomyślnie. Możesz się teraz zalogować.';

            $owner_id = mysqli_insert_id($conn);
            //wygeneruj kod listy 6 znakowy który nie istnieje w bazie danych
            $list_code = substr(str_shuffle(str_repeat("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", 6)), 0, 6);
            while (true) {
                $sql = "SELECT * FROM lists WHERE list_code = '$list_code'";
                $result = mysqli_query($conn, $sql);
                if (mysqli_num_rows($result) == 0) {
                    break;
                }
                $list_code = substr(str_shuffle(str_repeat("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", 6)), 0, 6);
            }
            
            $sql = "INSERT INTO lists (name, full_name, owner_id, list_code) VALUES ('ToDo', 'ToDo', '$owner_id', '$list_code')";
            if (mysqli_query($conn, $sql)) {
                $id = mysqli_insert_id($conn);
                
                //log
                $before = '';
                $after = 'Nazwa: Start ToDo <br/>Owner: '.$owner_id.' <br/>Kod: '.$list_code;
                $object_id = $id;
                $object_type="List";
                $action_type = '2';
                $desc = 'Dodano listę';
                include "../log_stripped_without_conn.php";
                //log

            }     


            header('Location: ../../login.php');
            exit();
        } else {
            session_start();
            $_SESSION['alert_type'] = "error";
            $_SESSION['alert'] = 'Błąd podczas rejestracji użytkownika. Spróbuj ponownie później.';
            header('Location: ../../register.php');
            exit();
        }
        
    }else {
        session_start();
        $_SESSION['alert_type'] = "error";
        $_SESSION['alert'] = 'Użytkownik o podanym adresie email już istnieje.';
        header('Location: ../../register.php');
        exit();
    }
}else{
    session_start();
    $_SESSION['alert_type'] = "error";
    $_SESSION['alert'] = 'Wszystkie pola muszą być wypełnione.';
    header('Location: ../../login.php');
    exit();
}
?>