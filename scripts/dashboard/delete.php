<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security.php';

$id = $_POST['id'];
$delete = $_POST['delete'];

if ($id != "" && $delete == "true") {
    
    include "../conn_db.php";
    
        $sql_old = "SELECT * FROM list_elements WHERE id = '$id'";
        $result_old = mysqli_query($conn, $sql_old);
        $row_old = mysqli_fetch_assoc($result_old);
        $sql = "DELETE FROM list_elements WHERE id = '$id'";
        if (mysqli_query($conn, $sql)) {
            
            //log
            $before = 'Nazwa: '.$row_old['title'].' <br/>Opis: '.$row_old['description'].' <br/>Deadline: '.$row_old['deadline_date'].' <br/>Lista: '.$row_old['list_id'];
            $after = '';
            $object_id = $id;
            $object_type="ListElement";
            $action_type = '3';
            $desc = 'Usunięto zadanie z listy';
            include "../log_stripped_without_conn.php";
            //log
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Usunięto zadanie'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Błąd usuwania zadania'
            ]);
        }
} else {
    echo json_encode([
        'status' => 'warning',
        'message' => 'Wypełnij wszystkie pola'
    ]);
}
?>