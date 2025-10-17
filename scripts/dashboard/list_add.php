<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security_scripts.php';

$name = $_POST['name'];

if ($name != "") {
    
    include "../conn_db.php";
    
        $owner_id = $_SESSION['login_id'];
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
        
        $sql = "INSERT INTO lists (name, full_name, owner_id, list_code) VALUES ('$name', '$name', '$owner_id', '$list_code')";
        if (mysqli_query($conn, $sql)) {
            $id = mysqli_insert_id($conn);
            
            //log
            $before = '';
            $after = 'Nazwa: '.$name.' <br/>Owner: '.$owner_id.' <br/>Kod: '.$list_code;
            $object_id = $id;
            $object_type="List";
            $action_type = '2';
            $desc = 'Dodano listę';
            include "../log_stripped_without_conn.php";
            //log
            echo json_encode([
                'status' => 'success',
                'message' => 'Dodano listę',
                'id' => $id
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Błąd dodawania listy'
            ]);
        }      
        
} else {
    echo json_encode([
        'status' => 'warning',
        'message' => 'Wypełnij wszystkie pola'
    ]);
}
?>