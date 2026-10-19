<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security_scripts.php';

$element_id = $_POST['element_id'];
$name = $_POST['name'];
$description = $_POST['description'];
$deadline = empty($_POST['deadline']) ? null : $_POST['deadline'];
$link_url = !empty($_POST['link_url']) && filter_var(trim($_POST['link_url']), FILTER_VALIDATE_URL) ? trim($_POST['link_url']) : null;


if ($name != "" && $element_id != "") {

    include "../conn_db.php";
        $sql_old = "SELECT list_elements.*, lists.list_type FROM list_elements LEFT JOIN lists ON lists.id=list_elements.list_id WHERE list_elements.id = '$element_id'";
        $result_old = mysqli_query($conn, $sql_old);
        $row_old = mysqli_fetch_assoc($result_old);

        // rutyna: pola powtarzania (porównanie z poprzednimi wartościami, żeby wykryć zmiany)
        $routine_set = '';
        $routine_changed = false;
        if ($row_old['list_type'] === 'routine') {
            include "../planner/planner_lib.php";
            $routine = hl_routine_fields_from_post();
            if (is_string($routine)) {
                echo json_encode(['status' => 'warning', 'message' => $routine]);
                exit;
            }
            if ($routine['_start'] !== null) $routine['recurrence_start'] = "'".$routine['_start']."'";
            unset($routine['_start']);
            foreach ($routine as $col => $val) {
                $routine_set .= ", $col = $val";
                $new = $val === 'NULL' ? null : trim($val, "'");
                if ((string)$row_old[$col] !== (string)$new) $routine_changed = true;
            }
            $deadline = $row_old['deadline_date'];
        }

        if($row_old['title'] != $name || $row_old['description'] != $description || $row_old['deadline_date'] != $deadline || $row_old['link_url'] != $link_url || $routine_changed) {

            $link_url_escaped = $link_url ? mysqli_real_escape_string($conn, $link_url) : null;
            $link_url_sql = $link_url_escaped ? "'$link_url_escaped'" : "NULL";
            $deadline_sql = !empty($deadline) ? "'" . mysqli_real_escape_string($conn, $deadline) . "'" : "NULL";
            $sql = "UPDATE list_elements SET title = '$name', description = '$description', deadline_date = $deadline_sql, link_url = $link_url_sql$routine_set WHERE id = '$element_id'";
            if (mysqli_query($conn, $sql)) {
                $id = mysqli_insert_id($conn);
                
                //log
                $before = 'Nazwa: '.$row_old['title'].' <br/>Opis: '.$row_old['description'].' <br/>Deadline: '.($row_old['deadline_date'] ?: 'brak');
                $after = 'Nazwa: '.$name.' <br/>Opis: '.$description.' <br/>Deadline: '.($deadline ?: 'brak');
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