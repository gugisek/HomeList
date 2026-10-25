<?php
include __DIR__."/conn_db.php";
require_once __DIR__.'/log_lib.php';
hl_log($conn, $object_id, $object_type, $before, $after, $action_type, $desc);
?>
