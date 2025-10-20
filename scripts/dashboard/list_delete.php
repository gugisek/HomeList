<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security_scripts.php';

$id = $_POST['id'];
$delete = $_POST['delete_confirm'];

if ($id != "" && $delete == "true") {
    
    include "../conn_db.php";
    
        $sql_old = "SELECT * FROM lists WHERE id = '$id'";
        $result_old = mysqli_query($conn, $sql_old);
        $row_old = mysqli_fetch_assoc($result_old);
        $sql = "UPDATE lists SET owner_id = '0' WHERE id = '$id'";
        if (mysqli_query($conn, $sql)) {
            
            //log
            $before = 'Nazwa: '.$row_old['name'].' <br/>Owner: '.$row_old['owner_id'].' <br/>Kod: '.$row_old['list_code'];
            $after = '';
            $object_id = $id;
            $object_type="List";
            $action_type = '3';
            $desc = 'Usunięto listę';
            include "../log_stripped_without_conn.php";
            //log
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Usunięto listę pomyślnie'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Błąd usuwania listy'
            ]);
        }
} else {
    echo json_encode([
        'status' => 'warning',
        'message' => 'Wypełnij wszystkie pola'
    ]);
}
?>