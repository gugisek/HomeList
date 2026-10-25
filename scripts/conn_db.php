<?php
//$conn = mysqli_connect("localhost", "eventy", "password", "eventy");
// conn_db.php bywa includowany kilka razy w jednym żądaniu (security.php + skrypt) – jedno połączenie wystarczy
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = mysqli_connect("localhost", "root", "", "homelist");
}
?>