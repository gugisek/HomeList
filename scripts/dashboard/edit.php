<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security_scripts.php';

$element_id = $_POST['element_id'];
$name = $_POST['name'];
$description = $_POST['description'];
$deadline = empty($_POST['deadline']) ? '0000-00-00 00:00:00' : $_POST['deadline'];
$link_url = !empty($_POST['link_url']) && filter_var(trim($_POST['link_url']), FILTER_VALIDATE_URL) ? trim($_POST['link_url']) : null;


if ($name != "" && $element_id != "") {

    include "../conn_db.php";
        $sql_old = "SELECT * FROM list_elements WHERE id = '$element_id'";
        $result_old = mysqli_query($conn, $sql_old);
        $row_old = mysqli_fetch_assoc($result_old);

        if($row_old['title'] != $name || $row_old['description'] != $description || $row_old['deadline_date'] != $deadline || $row_old['link_url'] != $link_url) {

            $link_url_escaped = $link_url ? mysqli_real_escape_string($conn, $link_url) : null;
            $link_url_sql = $link_url_escaped ? "'$link_url_escaped'" : "NULL";
            $sql = "UPDATE list_elements SET title = '$name', description = '$description', deadline_date = '$deadline', link_url = $link_url_sql WHERE id = '$element_id'";
            if (mysqli_query($conn, $sql)) {
                $id = mysqli_insert_id($conn);
                
                //log
                $before = 'Nazwa: '.$row_old['title'].' <br/>Opis: '.$row_old['description'].' <br/>Deadline: '.$row_old['deadline_date'];
                $after = 'Nazwa: '.$name.' <br/>Opis: '.$description.' <br/>Deadline: '.$deadline;
                $object_id = $element_id;
                $object_type="ListElement";
                $action_type = '1';
                $desc = 'Zmieniono dane zadania';
                include "../log_stripped_without_conn.php";
                //log
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Zmieniono dane zadania'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Błąd edycji zadania'
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