<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security.php';

$list_id = $_POST['id'];
$name = $_POST['name'];

if ($name != "" && $list_id != "") {
    
    include "../conn_db.php";
        $sql_old = "SELECT * FROM lists WHERE id = '$list_id'";
        $result_old = mysqli_query($conn, $sql_old);
        $row_old = mysqli_fetch_assoc($result_old);        

        if($row_old['full_name'] != $name) {
            
            $sql = "UPDATE lists SET name = '$name', full_name = '$name' WHERE id = '$list_id'";
            if (mysqli_query($conn, $sql)) {
                $id = mysqli_insert_id($conn);
                
                //log
                $before = 'Nazwa: '.$row_old['name'];
                $after = 'Nazwa: '.$name;
                $object_id = $list_id;
                $object_type="Lists";
                $action_type = '1';
                $desc = 'Zmieniono nazwę listy';
                include "../log_stripped_without_conn.php";
                //log
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Zmieniono nazwę listy'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Błąd edycji listy'
                ]);
            }    
        
    }else {
        echo json_encode([
            'status' => 'warning',
            'message' => 'Wprowadzono te same dane'
        ]);
    }
        
} else {
    echo json_encode([
        'status' => 'warning',
        'message' => 'Wypełnij wszystkie pola'
    ]);
}
?>