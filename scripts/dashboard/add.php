<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security_scripts.php';

$name = $_POST['name'];
$description = $_POST['description'];
$deadline = empty($_POST['deadline']) ? null : $_POST['deadline'];
$list = $_POST['list'];
$link_url = !empty($_POST['link_url']) && filter_var(trim($_POST['link_url']), FILTER_VALIDATE_URL) ? trim($_POST['link_url']) : null;

if ($name != "" && $list != "") {

    include "../conn_db.php";

        $creator_id = $_SESSION['login_id'];
        $create_date = date('Y-m-d H:i:s');
        $link_url_escaped = $link_url ? mysqli_real_escape_string($conn, $link_url) : null;
        //jeżeli deadline jest pusty to zapisz NULL zamiast daty zerowej
        $deadline_sql = !empty($deadline) ? "'" . mysqli_real_escape_string($conn, $deadline) . "'" : "NULL";

        // lista rutyn: dodatkowe pola powtarzania, bez deadline'u
        include "../planner/planner_lib.php";
        $list_type_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT list_type FROM lists WHERE id = '".(int)$list."'"));
        $routine_cols = '';
        $routine_vals = '';
        if ($list_type_row && $list_type_row['list_type'] === 'routine') {
            $routine = hl_routine_fields_from_post();
            if (is_string($routine)) {
                echo json_encode(['status' => 'warning', 'message' => $routine]);
                exit;
            }
            $routine['recurrence_start'] = "'".($routine['_start'] ?? date('Y-m-d'))."'";
            unset($routine['_start']);
            $deadline_sql = "NULL";
            $routine_cols = ', '.implode(', ', array_keys($routine));
            $routine_vals = ', '.implode(', ', $routine);
        }

        $sql = "INSERT INTO list_elements (title, description, deadline_date, list_id, status_id, creator_id, create_date, link_url$routine_cols) VALUES ('$name', '$description', $deadline_sql, '$list', '1', '$creator_id', '$create_date', " . ($link_url_escaped ? "'$link_url_escaped'" : "NULL") . "$routine_vals)";
        if (mysqli_query($conn, $sql)) {
            $id = mysqli_insert_id($conn);
            
            //log
            $before = '';
            $after = 'Nazwa: '.$name.' <br/>Opis: '.$description.' <br/>Deadline: '.($deadline ?: 'brak').' <br/>Lista: '.$list;
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