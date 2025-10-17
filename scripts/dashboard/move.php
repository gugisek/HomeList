<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security.php';

$element_id = $_POST['element_id'];
$list_id = $_POST['list_id'];

if ($list_id != "" && $element_id != "") {

    include "../conn_db.php";
        $sql_old = "SELECT * FROM list_elements WHERE id = '$element_id'";
        $result_old = mysqli_query($conn, $sql_old);
        $row_old = mysqli_fetch_assoc($result_old);        

        if($row_old['list_id'] != $list_id) {

            $sql = "UPDATE list_elements SET list_id = '$list_id' WHERE id = '$element_id'";
            if (mysqli_query($conn, $sql)) {
                $id = mysqli_insert_id($conn);
                
                //log
                $before = 'List ID: '.$row_old['list_id'];
                $after = 'List ID: '.$list_id;
                $object_id = $element_id;
                $object_type="ListElement";
                $action_type = '1';
                $desc = 'Zmieniono listę zadania';
                include "../log_stripped_without_conn.php";
                //log
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Zmieniono listę zadania'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Błąd zmiany listy zadania'
                ]);
            }    
        
    }else {
        echo json_encode([
            'status' => 'warning',
            'message' => 'wybrano tę samą listę'
        ]);
    }
        
} else {
    echo json_encode([
        'status' => 'warning',
        'message' => 'Wypełnij wszystkie pola'
    ]);
}
?>