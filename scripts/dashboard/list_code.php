<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security.php';

$code = $_POST['code'];

if ($code != "") {
    
    include "../conn_db.php";
    
        $user_id = $_SESSION['login_id'];
        // owner nie równa się user_id
        $sql = "Select id from lists where list_code='$code' and owner_id != $user_id;";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $list_id = $row['id'];
            
            $sql_check = "SELECT * FROM list_invities WHERE list_id = $list_id AND user_id = $user_id;";
            $result_check = mysqli_query($conn, $sql_check);
            if (mysqli_num_rows($result_check) == 0) {
                $sql = "INSERT INTO list_invities (list_id, user_id) VALUES ('$list_id', '$user_id')";
                if (mysqli_query($conn, $sql)) {
                    //log
                    $before = '';
                    $after = 'Kod: '.$code.' <br/>Lista: '.$list_id.' <br/>Użytkownik: '.$user_id;
                    $object_id = $list_id;
                    $object_type="List";
                    $action_type = '2';
                    $desc = 'Dodano użytkownika do listy';
                    include "../log_stripped_without_conn.php";
                    //log
                    
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Dodano użytkownika do listy',
                        'id' => $list_id
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Błąd dodawania użytkownika do listy'
                    ]);
                }
            } else {
                echo json_encode([
                    'status' => 'warning',
                    'message' => 'Masz już dodaną tę listę'
                ]);
            }
            
        } else {
            echo json_encode([
                'status' => 'warning',
                'message' => 'Nie znaleziono listy możliwej do dodania o podanym kodzie'
            ]);
        }
          
        
} else {
    echo json_encode([
        'status' => 'warning',
        'message' => 'Wypełnij wszystkie pola'
    ]);
}
?>