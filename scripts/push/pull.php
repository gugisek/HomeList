<?php
// Wywoływane przez service worker po otrzymaniu pusha: zwraca i usuwa oczekujące powiadomienia urządzenia.
// Autoryzacją jest sam endpoint subskrypcji (tajny URL znany tylko przeglądarce i serwerowi).
header('Content-Type: application/json');
include '../conn_db.php';

$hash = sha1(trim($_POST['endpoint'] ?? ''));
$sub = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM push_subscriptions WHERE endpoint_hash='$hash'"));
if (!$sub) {
    echo '[]';
    exit();
}
$sid = (int)$sub['id'];
$out = [];
$result = mysqli_query($conn, "SELECT id, title, body, tag, url FROM push_queue WHERE subscription_id='$sid' ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($result)) $out[] = $row;
if ($out) mysqli_query($conn, "DELETE FROM push_queue WHERE subscription_id='$sid' AND id<=".(int)end($out)['id']);
echo json_encode($out);
