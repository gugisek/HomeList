<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security.php';

$name = $_POST['name'];
$description = $_POST['description'];
$deadline = $_POST['deadline'];
$list = $_POST['list'];

if ($name != "" && $list != "") {
    
    include "../conn_db.php";
    
        $creator_id = $_SESSION['login_id'];
        $create_date = date('Y-m-d H:i:s');
        $sql = "INSERT INTO list_elements (title, description, deadline_date, list_id, status_id, creator_id, create_date) VALUES ('$name', '$description', '$deadline', '$list', '1', '$creator_id', '$create_date')";
        if (mysqli_query($conn, $sql)) {
            $id = mysqli_insert_id($conn);
            
            //log
            $before = '';
            $after = 'Nazwa: '.$name.' <br/>Opis: '.$description.' <br/>Deadline: '.$deadline.' <br/>Lista: '.$list;
            $object_id = $id;
            $object_type="ListElement";
            $action_type = '2';
            $desc = 'Dodano zadanie do listy';
            include "../log_stripped_without_conn.php";
            //log
            echo json_encode([
                'status' => 'success',
                'message' => 'Dodano zadanie'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Błąd dodawania zadania'
            ]);
        }      
        
} else {
    echo json_encode([
        'status' => 'warning',
        'message' => 'Wypełnij wszystkie pola'
    ]);
}
?>