<?php
include '../../security.php';

$id = $_POST['id'];
include "../../conn_db.php";

    if($id != "") {
        $sql_old = "Select * from faq where id='$id'";
        $result_old = $conn->query($sql_old);
        $row_old = $result_old->fetch_assoc();

        $sql = "DELETE FROM faq WHERE id='$id';";
        if ($conn->query($sql) === TRUE) {
            //log
                    $before = 'Pytanie: ' . $row_old['question'] . ' <br/> Odpowiedź: ' . $row_old['answer'];
                    $after = "";
                    $object_id=$id;
                    $object_type="faq";
                    $action_type="3";
                    $desc="Usunięto FAQ";
                    include "../../log.php";
            //log
            $_SESSION['alert'] = 'Pomyślnie usunięto FAQ';
            $_SESSION['alert_type'] = 'success';
        } else {
            $_SESSION['alert'] = 'Wystąpił błąd podczas usuwania FAQ';
            $_SESSION['alert_type'] = 'error';
        }
    } else {
        $_SESSION['alert'] = 'Nie wybrano FAQ';
        $_SESSION['alert_type'] = 'warning';
    }

header("Location: ../../../panel.php");

?>