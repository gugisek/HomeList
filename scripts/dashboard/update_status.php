<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security.php';

$element_id = $_POST['element_id'];
$status_id = $_POST['status_id'];

if ($element_id != "" && $status_id != "") {
    
    include "../conn_db.php";
        if($status_id == 2){
            $sql = "UPDATE list_elements SET status_id = '$status_id', done_date = NOW() WHERE id = '$element_id'";
        }else{
            $sql = "UPDATE list_elements SET status_id = '$status_id' WHERE id = '$element_id'";
        }
        if (mysqli_query($conn, $sql)) {
            $id = mysqli_insert_id($conn);
            
            //log
            $before = '';
            $after = 'Status: '.$status_id;
            $object_id = $element_id;
            $object_type="ListElement";
            $action_type = '1';
            $desc = 'Zmieniono status zadania';
            include "../log_stripped_without_conn.php";
            //log
            echo json_encode([
                'status' => 'success',
                'message' => 'Pomyślnie zmieniono status zadania'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Błąd zmiany statusu zadania'
            ]);
        }      
        
} else {
    echo json_encode([
        'status' => 'warning',
        'message' => 'Brak wymaganych danych'
    ]);
}
?>