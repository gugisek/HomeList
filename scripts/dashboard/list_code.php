<?php
header('Content-Type: application/json'); // Nagłówek JSON

$SECURITY_REQUIRED_MODULE = ['dashboard', 'add'];
include '../security_scripts.php';
include "../conn_db.php";
include "../access.php";

$code = strtoupper(trim((string)($_POST['code'] ?? '')));
if ($code === '') {
    hl_json_exit('warning', 'Wypełnij wszystkie pola');
}

$user_id = (int)$_SESSION['login_id'];
// lista usunięta przez właściciela (owner_id = 0) nie przyjmuje nowych osób
$stmt = $conn->prepare("SELECT id FROM lists WHERE list_code = ? AND owner_id <> ? AND owner_id <> 0 LIMIT 1");
$stmt->bind_param('si', $code, $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    hl_json_exit('warning', 'Nie znaleziono listy możliwej do dodania o podanym kodzie');
}
$list_id = (int)$row['id'];

if (hl_list_access($conn, $user_id, $list_id)) {
    hl_json_exit('warning', 'Masz już dodaną tę listę');
}

$stmt = $conn->prepare('INSERT INTO list_invities (list_id, user_id) VALUES (?, ?)');
$stmt->bind_param('ii', $list_id, $user_id);
if ($stmt->execute()) {
    //log
    $before = '';
    $after = 'Kod: '.$code.' <br/>Lista: '.$list_id.' <br/>Użytkownik: '.$user_id;
    $object_id = $list_id;
    $object_type="List";
    $action_type = '2';
    $desc = 'Dodano użytkownika do listy';
    include "../log_stripped_without_conn.php";
    //log
    hl_json_exit('success', 'Dodano użytkownika do listy', ['id' => $list_id]);
}
hl_json_exit('error', 'Błąd dodawania użytkownika do listy');
